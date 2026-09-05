<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;

final class ValidateInputStep implements AgentStepInterface
{
    public function id(): string
    {
        return 'validate_input';
    }

    public function label(): string
    {
        return __('Validation des paramètres', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $input = $context->input();

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
                return StepResult::failed(
                    sprintf(
                        __('Le champ "%s" est obligatoire.', MY_AI_AGENT_DOMAIN),
                        $field
                    )
                );
            }
        }

        return StepResult::continue();
    }
}
