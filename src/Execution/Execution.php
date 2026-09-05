<?php

declare(strict_types=1);

namespace MyAIAgent\Execution;

final class Execution
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string $id,
        private readonly string $agentId,
        private readonly array $input,
        private string $status = ExecutionStatus::PENDING,
        private int $currentStep = 0,
        private array $data = [],
        private ?string $error = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function agentId(): string
    {
        return $this->agentId;
    }

    /**
     * @return array<string, mixed>
     */
    public function input(): array
    {
        return $this->input;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function currentStep(): int
    {
        return $this->currentStep;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function start(): void
    {
        $this->status = ExecutionStatus::RUNNING;
        $this->error = null;
    }

    public function setStep(int $step): void
    {
        $this->currentStep = max(0, $step);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function mergeData(array $data): void
    {
        $this->data = array_replace(
            $this->data,
            $data
        );
    }

    public function waitForHuman(): void
    {
        $this->status = ExecutionStatus::WAITING_HUMAN;
    }

    public function complete(): void
    {
        $this->status = ExecutionStatus::COMPLETED;
    }

    public function fail(string $error): void
    {
        $this->status = ExecutionStatus::FAILED;
        $this->error = $error;
    }

    public function cancel(): void
    {
        $this->status = ExecutionStatus::CANCELLED;
    }

    public function isWaitingHuman(): bool
    {
        return $this->status === ExecutionStatus::WAITING_HUMAN;
    }

    public function isFinished(): bool
    {
        return in_array(
            $this->status,
            [
                ExecutionStatus::COMPLETED,
                ExecutionStatus::FAILED,
                ExecutionStatus::CANCELLED,
            ],
            true
        );
    }
}
