<?php


declare(strict_types=1);

namespace MyAIAgent\Agent;

interface AgentStepInterface
{
    /**
     * Unique identifier of the step.
     */
    public function id(): string;

    /**
     * Human-readable label.
     */
    public function label(): string;

    /**
     * Execute this step.
     */
    public function execute(AgentContext $context): StepResult;
}
