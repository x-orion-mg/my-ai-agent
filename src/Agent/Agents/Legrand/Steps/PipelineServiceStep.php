<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand\Steps;

use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Core\Plugin;
use MyAIAgent\Pipeline\Service\PipelineService;

final class PipelineServiceStep implements AgentStepInterface
{
    public function id(): string
    {
        return 'pipeline-Service';
    }

    public function label(): string
    {
        return 'Lancement Du Pipeline Service Cron';
    }

    public function execute(AgentContext $context): StepResult
    {
        $importId = $context->get('import_id');
        $pipeline = Plugin::instance()
            ->container()
            ->get(PipelineService::class);

        $pipeline->processImport(
            $importId
        );

        return StepResult::continue(
            data: [
                'message' => __('Le cron est lancé automatiquement', MY_AI_AGENT_DOMAIN),
            ],
        );

    }
}
