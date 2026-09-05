<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Test;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\AgentStepInterface;

final class TestAgent implements AgentInterface
{
    public function id(): string
    {
        return 'test';
    }

    public function name(): string
    {
        return 'Test AI Agent';
    }

    public function formSchema(): array
    {
        return [
            'fields' => [
                [
                    'name' => 'message',
                    'type' => 'textarea',
                    'label' => 'Message',
                    'required' => true,
                ],
            ],
        ];
    }

    public function steps(): array
    {
        return [
            new TestStep(),
        ];
    }

    public function validate(array $input): void
    {
        if (
            !isset($input['message'])
            || trim((string) $input['message']) === ''
        ) {
            throw new InvalidArgumentException(
                'Le message est obligatoire.'
            );
        }
    }
}
