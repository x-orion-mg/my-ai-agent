<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\Agents\Blog\Steps\ValidateInputStep;

final class BlogAgent implements AgentInterface
{
    public function id(): string
    {
        return 'generate-blog';
    }

    public function name(): string
    {
        return __('Générateur d’articles de blog', MY_AI_AGENT_DOMAIN);
    }

    /**
     * @return array<string, mixed>
     */
    public function formSchema(): array
    {
        return [
            'theme' => [
                'type' => 'text',
                'required' => true,
            ],
            'language' => [
                'type' => 'select',
                'required' => true,
            ],
            'tone' => [
                'type' => 'select',
                'required' => true,
            ],
            'provider' => [
                'type' => 'select',
                'required' => true,
            ],
            'prompt' => [
                'type' => 'select',
                'required' => true,
            ],
        ];
    }

    /**
     * @return array<int, AgentStepInterface>
     */
    public function steps(): array
    {
        return [
            new ValidateInputStep(),
        ];
    }


    /**
     * @param array<string, mixed> $input
     */
    public function validate(array $input): void
    {
        $required = [
            'theme',
            'language',
            'tone',
            'provider',
            'prompt',
        ];

        foreach ($required as $field) {
            if (
                !isset($input[$field])
                || trim((string) $input[$field]) === ''
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        __('Le champ "%s" est obligatoire.', MY_AI_AGENT_DOMAIN),
                        $field
                    )
                );
            }
        }
    }
}
