<?php

declare(strict_types=1);

namespace MyAIAgent\Agent;

use MyAIAgent\AI\AIService;

final class AgentContext
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $executionId,
        private readonly array $input,
        private array $data,
        private readonly AIService $ai,
    ) {
    }

    public function executionId(): string
    {
        return $this->executionId;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->data[$key]
            ?? $this->input[$key]
            ?? $default;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = array_replace(
            $this->data,
            $data
        );
    }

    public function ai(): AIService
    {
        return $this->ai;
    }
}
