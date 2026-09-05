<?php

declare(strict_types=1);

namespace MyAIAgent\Agent;

interface AgentInterface
{
    public function id(): string;

    public function name(): string;

    /**
     * @return array<string, mixed>
     */
    public function formSchema(): array;

    /**
     * @return array<int, AgentStepInterface>
     */
    public function steps(): array;

    /**
     * @param array<string, mixed> $input
     */
    public function validate(array $input): void;
}
