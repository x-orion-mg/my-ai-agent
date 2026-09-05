<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;

final class AskAiStep implements AgentStepInterface
{

    public function id(): string
    {
        return 'ask_ai';
    }

    public function label(): string
    {
        return __('Demande à l\'IA', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $prompt = $context->get('prompt');

        if (! is_string($prompt) || trim($prompt) === '') {
            return StepResult::failed(
                'Le prompt est manquant.'
            );
        }

        $provider = $context->get('provider');

        if (! is_string($provider) || trim($provider) === '') {
            return StepResult::failed(
                'Aucun fournisseur IA n’a été sélectionné.'
            );
        }

        try {
            $result = $context->ai()->ask(
                $provider,
                $prompt,
                [
                    'session_id' => $context->executionId(),
                ]
            );

            return StepResult::continue([
                'ai_response' => $result->text(),
                'ai_provider' => $result->provider(),
                'ai_model' => $result->model(),
                'ai_finish_reason' => $result->finishReason(),
                'ai_usage' => $result->usage(),
            ]);
        } catch (\Throwable $exception) {
            return StepResult::failed(
                $exception->getMessage()
            );
        }
    }

}
