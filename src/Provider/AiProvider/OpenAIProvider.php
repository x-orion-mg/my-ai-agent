<?php

declare(strict_types=1);

namespace MyAIAgent\Provider\AiProvider;

use MyAIAgent\Provider\AbstractProvider;

/**
 * OpenAI Chat Completions provider with vision support.
 */
final class OpenAIProvider extends AbstractProvider
{
    private const DEFAULT_MODEL    = 'gpt-4o-mini';

    public function slug(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

}
