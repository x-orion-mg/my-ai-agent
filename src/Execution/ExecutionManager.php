<?php

declare(strict_types=1);

namespace MyAIAgent\Execution;

use MyAIAgent\AI\AIService;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentManager;
use MyAIAgent\Repository\ExecutionRepository;
use Throwable;

final class ExecutionManager
{
    public function __construct(
        private readonly AgentManager $agentManager,
        private readonly AIService $aiService,
        private readonly ExecutionRepository $repository,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(
        string $agentId,
        array $input
    ): Execution {
        $agent = $this->agentManager->get($agentId);

        $agent->validate($input);

        $execution = new Execution(
            id: wp_generate_uuid4(),
            agentId: $agentId,
            input: $input,
        );

        $userId = get_current_user_id();

        $insertedId = $this->repository->create(
            uuid: $execution->id(),
            agentId: $execution->agentId(),
            userId: $userId,
            input: $execution->input(),
        );

        if ($insertedId <= 0) {
            throw new \RuntimeException(
                'Impossible de créer l\'execution.'
            );
        }

        return $execution;
    }

    public function find(string $uuid): ?Execution
    {
        return $this->repository->findByUuid($uuid);
    }

    public function run(Execution $execution): Execution
    {
        if ($execution->isFinished()) {
            return $execution;
        }

        if ($execution->isWaitingHuman()) {
            return $execution;
        }

        $agent = $this->agentManager->get(
            $execution->agentId()
        );

        $steps = $agent->steps();

        if (!isset($steps[$execution->currentStep()])) {
            $execution->complete();

            $this->repository->update($execution);

            return $execution;
        }

        try {
            $execution->start();

            $context = new AgentContext(
                executionId: $execution->id(),
                input: $execution->input(),
                data: $execution->data(),
                ai: $this->aiService,
            );

            $step = $steps[$execution->currentStep()];

            $result = $step->execute($context);

            if ($result->isFailed()) {
                $execution->fail(
                    $result->message()
                    ?? 'Erreur pendant l\'exécution du step.'
                );

                $this->repository->update($execution);

                return $execution;
            }

            $execution->mergeData(
                $result->data()
            );

            if ($result->isWaitingHuman()) {
                $execution->waitForHuman();

                $this->repository->update($execution);

                return $execution;
            }

            $execution->setStep(
                $execution->currentStep() + 1
            );

            if (
                !isset(
                    $steps[$execution->currentStep()]
                )
            ) {
                $execution->complete();
            }

            $this->repository->update($execution);
        } catch (Throwable $exception) {
            $execution->fail(
                $exception->getMessage()
            );

            $this->repository->update($execution);
        }

        return $execution;
    }
}
