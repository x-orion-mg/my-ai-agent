<?php

declare(strict_types=1);

namespace MyAIAgent\Execution;

final class StepExecutionResult
{
    /**
     * @param array<string, mixed> $result
     */
    public function __construct(
        private readonly int $stepIndex,
        private readonly string $stepId,
        private readonly string $stepLabel,
        private readonly string $stepStatus,
        private readonly array $result,
        private readonly ?array $nextStep = null,
        private readonly int $totalSteps = 0,
        private readonly int $completedSteps = 0,
    ) {
    }

    public function stepIndex(): int
    {
        return $this->stepIndex;
    }

    public function stepId(): string
    {
        return $this->stepId;
    }

    public function stepLabel(): string
    {
        return $this->stepLabel;
    }

    public function stepStatus(): string
    {
        return $this->stepStatus;
    }

    /**
     * @return array<string, mixed>
     */
    public function result(): array
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nextStep(): ?array
    {
        return $this->nextStep;
    }

    public function totalSteps(): int
    {
        return $this->totalSteps;
    }

    public function completedSteps(): int
    {
        return $this->completedSteps;
    }

    public function progress(): int
    {
        if ($this->totalSteps === 0) {
            return 0;
        }

        return (int) round(
            ($this->completedSteps / $this->totalSteps) * 100
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'step' => [
                'index'  => $this->stepIndex,
                'id'     => $this->stepId,
                'label'  => $this->stepLabel,
                'status' => $this->stepStatus,
            ],
            'result' => $this->result,
            'next_step' => $this->nextStep,
            'progress' => [
                'current' => $this->completedSteps,
                'total'   => $this->totalSteps,
                'percent' => $this->progress(),
            ],
        ];
    }
}
