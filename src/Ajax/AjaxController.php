<?php

declare(strict_types=1);

namespace MyAIAgent\Ajax;

use MyAIAgent\Execution\ExecutionManager;
use Throwable;

final class AjaxController extends AbstractController
{
    public function __construct(
        private readonly ExecutionManager $executionManager,
    ) {
    }

    /**
     * Create a new execution.
     *
     * This method does NOT execute any step.
     *
     * It only:
     * - validates the request
     * - creates the execution
     * - returns the step definitions
     *
     * The frontend will then call run() one step at a time.
     */
    public function create(): void
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

            $steps = $this->executionManager->stepDefinitions(
                $execution
            );

            $this->success([
                'message' => __('Execution créée.', MY_AI_AGENT_DOMAIN),

                'execution_id' => $execution->id(),

                'agent' => $execution->agentId(),

                'status' => $execution->status(),

                'current_step' => $execution->currentStep(),

                'steps' => $steps,

                'data' => $execution->data(),

                'error' => $execution->error(),
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                400
            );
        }
    }

    /**
     * Execute exactly ONE step.
     *
     * The frontend calls this method repeatedly until the execution:
     *
     * - reaches waiting_human
     * - completes
     * - fails
     */
    public function run(): void
    {
        $this->guard();

        $executionId = $this->post('execution_id');

        if ($executionId === '') {
            $this->fail(
                __('Execution manquante.', MY_AI_AGENT_DOMAIN),
                400
            );
        }

        try {
            $execution = $this->executionManager->find(
                $executionId
            );

            if ($execution === null) {
                $this->fail(
                    __('Execution introuvable.', MY_AI_AGENT_DOMAIN),
                    404
                );
            }

            $result = $this->executionManager->run(
                $execution
            );

            $this->success([
                'message' => __('Step exécuté.', MY_AI_AGENT_DOMAIN),

                'execution_id' => $execution->id(),

                'agent' => $execution->agentId(),

                'status' => $execution->status(),

                'current_step' => $execution->currentStep(),

                'step' => $result->toArray(),

                'data' => $execution->data(),

                'error' => $execution->error(),
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                400
            );
        }
    }

    /**
     * Resume an execution after human validation.
     *
     * This method does NOT execute the next step.
     *
     * It only changes the execution state from:
     *
     * waiting_human
     *
     * to:
     *
     * running
     *
     * The frontend will then call run().
     */
    public function validate(): void
    {
        $this->guard();

        $executionId = $this->post('execution_id');

        if ($executionId === '') {
            $this->fail(
                __('Execution manquante.', MY_AI_AGENT_DOMAIN),
                400
            );
        }

        try {
            $execution = $this->executionManager->find(
                $executionId
            );

            if ($execution === null) {
                $this->fail(
                    __('Execution introuvable.', MY_AI_AGENT_DOMAIN),
                    404
                );
            }

            if (! $execution->isWaitingHuman()) {
                $this->fail(
                    __(
                        'Cette execution n\'attend pas de validation humaine.',
                        MY_AI_AGENT_DOMAIN
                    ),
                    400
                );
            }

            /*
             * Optional validation data coming from the frontend.
             *
             * Example:
             *
             * [
             *     'approved' => true,
             *     'comment' => 'Le contenu est correct.'
             * ]
             */
            $validation = [];

            if (isset($_POST['validation'])) {
                $validation = wp_unslash($_POST['validation']);

                if (! is_array($validation)) {
                    $validation = [];
                }
            }

            /*
             * Store validation information in the execution data.
             *
             * This allows a future HumanValidationStep or another step
             * to access the human decision.
             */
            if ($validation !== []) {
                $execution->mergeData([
                    'human_validation' => $validation,
                ]);
            }

            $execution = $this->executionManager->resume(
                $execution
            );

            $this->success([
                'message' => __('Validation enregistrée.', MY_AI_AGENT_DOMAIN),

                'execution_id' => $execution->id(),

                'agent' => $execution->agentId(),

                'status' => $execution->status(),

                'current_step' => $execution->currentStep(),

                'data' => $execution->data(),

                'error' => $execution->error(),
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                400
            );
        }
    }

    /**
     * Get the current state of an execution.
     *
     * Useful if the frontend reloads the page or loses connection.
     *
     * This method does NOT execute anything.
     */
    public function status(): void
    {
        $this->guard();

        $executionId = $this->post('execution_id');

        if ($executionId === '') {
            $this->fail(
                __('Execution manquante.', MY_AI_AGENT_DOMAIN),
                400
            );
        }

        try {
            $execution = $this->executionManager->find(
                $executionId
            );

            if ($execution === null) {
                $this->fail(
                    __('Execution introuvable.', MY_AI_AGENT_DOMAIN),
                    404
                );
            }

            $steps = $this->executionManager->stepDefinitions(
                $execution
            );

            $this->success([
                'execution_id' => $execution->id(),

                'agent' => $execution->agentId(),

                'status' => $execution->status(),

                'current_step' => $execution->currentStep(),

                'steps' => $steps,

                'data' => $execution->data(),

                'error' => $execution->error(),
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                400
            );
        }
    }
}
