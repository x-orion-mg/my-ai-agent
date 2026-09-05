<?php

declare(strict_types=1);

namespace MyAIAgent\AI;

final class AIResult
{
    /**
     * @param array<string, mixed> $usage
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $text,
        private readonly string $provider,
        private readonly string $model,
        private readonly string $finishReason,
        private readonly array $usage,
        private readonly array $metadata,
    ) {
    }

    public function text(): string
    {
        return $this->text;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function finishReason(): string
    {
        return $this->finishReason;
    }

    /**
     * @return array<string, mixed>
     */
    public function usage(): array
    {
        return $this->usage;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
