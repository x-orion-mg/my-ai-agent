<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\StepResult;
final class HumanValidationStep implements AgentStepInterface
{
    public function id(): string
    {
        return 'human-validation';
    }

    public function label(): string
    {
        return 'Validation humaine';
    }

    public function execute(AgentContext $context): StepResult
    {
        /*return StepResult::waitingHuman(
            data: [
                'validation_required' => true,
            ],
        );*/
        return StepResult::continue(
            data: [
                'message' => __('Veuillez valider le contenu généré par l’IA avant de créer l’article.', MY_AI_AGENT_DOMAIN),
                'validation_required' => false,
            ],
        );

    }
}
