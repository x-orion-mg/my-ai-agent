<?php


declare(strict_types=1);

namespace MyAIAgent\Execution;

use MyAIAgent\Agent\AgentResult;

final class ExecutionResult
{
    public function __construct(
        private readonly AgentResult $agentResult,
        private readonly string      $status = ExecutionStatus::COMPLETED,
        private readonly ?int        $nextStep = null,
    )
    {
    }

    public function agentResult(): AgentResult
    {
        return $this->agentResult;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function nextStep(): ?int
    {
        return $this->nextStep;
    }
}
