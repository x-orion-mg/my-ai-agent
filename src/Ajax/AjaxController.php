<?php
declare(strict_types=1);

namespace MyAIAgent\Ajax;

use MyAIAgent\Execution\ExecutionManager;

final class AjaxController extends AbstractController
{
    private ExecutionManager $executionManager;

    public function __construct(ExecutionManager $executionManager)
    {
        $this->executionManager = $executionManager;
    }

    public function execute(): void
    {
        $this->guard();

        $agentId = $this->post('agent');

        if ($agentId === '') {
            $this->fail(
                __('Agent manquant.', MY_AI_AGENT_DOMAIN),
                400
            );
        }

        $input = wp_unslash($_POST);

        unset(
            $input['action'],
            $input['nonce']
        );

        try {
            $execution = $this->executionManager->create(
                agentId: $agentId,
                input: $input
            );
            $execution = $this->executionManager->run($execution);
            $execution = $this->executionManager->run($execution);
            $this->success([
                'message' => __('Execution terminée.', MY_AI_AGENT_DOMAIN),
                'execution_id' => $execution->id(),
                'agent' => $execution->agentId(),
                'status' => $execution->status(),
                'step' => $execution->currentStep(),
                'data' => $execution->data(),
                'error' => $execution->error(),
            ]);

        } catch (\Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                400
            );
        }
    }

}