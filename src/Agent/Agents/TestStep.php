<?php


declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Test;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;

final class TestStep implements AgentStepInterface
{
    public function id(): string
    {
        return 'generate';
    }

    public function label(): string
    {
        return 'Génération IA';
    }

    public function execute(
        AgentContext $context
    ): StepResult
    {
        $message = (string)$context->get(
            'message'
        );

        $result = $context->ai()->ask(
            $message,
            [
                'provider' => 'openrouter',
                'api_key' => '-',
                'models_list' => [
                    'openai/gpt-5',
                    'nvidia/nemotron-3-ultra-550b-a55b:free',
                ],
            ]
        );

        return StepResult::continue([
            'content' => $result->text(),
            'ai' => [
                'provider' => $result->provider(),
                'model' => $result->model(),
                'finish_reason' => $result->finishReason(),
                'usage' => $result->usage(),
                'metadata' => $result->metadata(),
            ],
        ]);
    }
}
