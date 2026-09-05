<?php

declare(strict_types=1);

namespace MyAIAgent\Execution;

use MyAIAgent\AI\AIService;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentManager;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Repository\ExecutionRepository;
use RuntimeException;
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
            throw new RuntimeException(
                'Impossible de créer l\'execution.'
            );
        }

        return $execution;
    }

    public function find(string $uuid): ?Execution
    {
        return $this->repository->findByUuid($uuid);
    }

    /**
     * Execute exactly one step.
     */
    public function run(
        Execution $execution
    ): StepExecutionResult {
        if ($execution->isFinished()) {
            throw new RuntimeException(
                'Cette exécution est déjà terminée.'
            );
        }

        if ($execution->isWaitingHuman()) {
            throw new RuntimeException(
                'Cette exécution attend une validation humaine.'
            );
        }

        $agent = $this->agentManager->get(
            $execution->agentId()
        );

        $steps = $agent->steps();

        $stepIndex = $execution->currentStep();

        if (! isset($steps[$stepIndex])) {
            $execution->complete();

            $this->repository->update($execution);

            throw new RuntimeException(
                'Aucun step disponible pour cette exécution.'
            );
        }

        $step = $steps[$stepIndex];

        $totalSteps = count($steps);

        $execution->start();

        $context = new AgentContext(
            executionId: $execution->id(),
            input: $execution->input(),
            data: $execution->data(),
            ai: $this->aiService,
        );

        try {
            $result = $step->execute($context);
        } catch (Throwable $exception) {
            $execution->fail(
                $exception->getMessage()
            );

            $this->repository->update($execution);

            return $this->result(
                execution: $execution,
                step: $step,
                stepIndex: $stepIndex,
                totalSteps: $totalSteps,
                status: 'failed',
                data: [
                    'error' => $exception->getMessage(),
                ]
            );
        }

        $execution->mergeData(
            $result->data()
        );

        /*
         * Le step a échoué.
         */
        if ($result->isFailed()) {
            $execution->fail(
                $result->message()
                ?? 'Erreur pendant l\'exécution du step.'
            );

            $this->repository->update($execution);

            return $this->result(
                execution: $execution,
                step: $step,
                stepIndex: $stepIndex,
                totalSteps: $totalSteps,
                status: 'failed',
                data: $result->data()
            );
        }

        /*
         * Le step demande une validation humaine.
         */
        if ($result->isWaitingHuman()) {
            $execution->waitForHuman();

            $this->repository->update($execution);

            return $this->result(
                execution: $execution,
                step: $step,
                stepIndex: $stepIndex,
                totalSteps: $totalSteps,
                status: 'waiting_human',
                data: $result->data()
            );
        }

        /*
         * Le step est terminé avec succès.
         */
        $execution->setStep(
            $stepIndex + 1
        );

        /*
         * Dernier step.
         */
        if (! isset($steps[$stepIndex + 1])) {
            $execution->complete();

            $this->repository->update($execution);

            return $this->result(
                execution: $execution,
                step: $step,
                stepIndex: $stepIndex,
                totalSteps: $totalSteps,
                status: 'completed',
                data: $result->data()
            );
        }

        /*
         * Il reste des steps.
         */
        $this->repository->update($execution);

        return $this->result(
            execution: $execution,
            step: $step,
            stepIndex: $stepIndex,
            totalSteps: $totalSteps,
            status: 'completed',
            data: $result->data()
        );
    }

    /**
     * Resume an execution after human validation.
     */
    public function resume(
        Execution $execution
    ): Execution {
        if (! $execution->isWaitingHuman()) {
            throw new RuntimeException(
                'Cette exécution ne nécessite pas de validation humaine.'
            );
        }

        $execution->start();

        $this->repository->update($execution);

        return $execution;
    }

    /**
     * Return the steps of an agent for the frontend.
     *
     * @return array<int, array{id: string, label: string}>
     */
    public function stepDefinitions(
        Execution $execution
    ): array {
        $agent = $this->agentManager->get(
            $execution->agentId()
        );

        return array_map(
            static function (
                AgentStepInterface $step
            ): array {
                return [
                    'id'    => $step->id(),
                    'label' => $step->label(),
                ];
            },
            $agent->steps()
        );
    }

    /**
     * Build the result returned after one step.
     *
     * @param array<string, mixed> $data
     */
    private function result(
        Execution $execution,
        AgentStepInterface $step,
        int $stepIndex,
        int $totalSteps,
        string $status,
        array $data
    ): StepExecutionResult {
        $nextStep = null;

        if ($status === 'completed') {
            $nextIndex = $stepIndex + 1;

            $agent = $this->agentManager->get(
                $execution->agentId()
            );

            $steps = $agent->steps();

            if (isset($steps[$nextIndex])) {
                $nextStep = [
                    'index' => $nextIndex,
                    'id'    => $steps[$nextIndex]->id(),
                    'label' => $steps[$nextIndex]->label(),
                ];
            }
        }

        $completedSteps = $stepIndex;

        if ($status === 'completed') {
            $completedSteps = $stepIndex + 1;
        }

        return new StepExecutionResult(
            stepIndex: $stepIndex,
            stepId: $step->id(),
            stepLabel: $step->label(),
            stepStatus: $status,
            result: $data,
            nextStep: $nextStep,
            totalSteps: $totalSteps,
            completedSteps: $completedSteps,
        );
    }
}
