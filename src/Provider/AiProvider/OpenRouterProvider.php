<?php

declare(strict_types=1);

namespace MyAIAgent\Provider\AiProvider;



use MyAIAgent\Provider\AbstractProvider;

/**
 * OpenRouter provider. Uses the OpenAI-compatible schema, giving access to many
 * models through a single endpoint.
 */
final class OpenRouterProvider extends AbstractProvider
{

    public function slug(): string
    {
        return 'openrouter';
    }

    public function label(): string
    {
        return 'OpenRouter';
    }
}
