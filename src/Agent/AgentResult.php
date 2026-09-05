<?php


declare(strict_types=1);

namespace MyAIAgent\Agent;

use MyAIAgent\AI\AIResult;

final class AgentResult
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string    $status,
        private readonly array     $data = [],
        private readonly ?AIResult $aiResult = null,
    )
    {
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function aiResult(): ?AIResult
    {
        return $this->aiResult;
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }
}
