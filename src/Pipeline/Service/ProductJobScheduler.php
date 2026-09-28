<?php

declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use MyAIAgent\Core\Plugin;

final class ProductJobScheduler
{
    public const HOOK = 'my_ai_agent_run_product_job';

    public function register(): void
    {
        add_action(
            self::HOOK,
            function (int $jobId): void {

                error_log(
                    'HOOK EXECUTED - jobId = ' . $jobId
                );

                $pipeline = Plugin::instance()
                    ->container()
                    ->get(PipelineService::class);

                $pipeline->processJob($jobId);
            }
        );
    }

    public function schedule(
        int $jobId,
        int $delay = 0
    ): void {

        $timestamp = time() + max(0, $delay);

        if (function_exists('as_schedule_single_action')) {

            as_schedule_single_action(
                $timestamp,
                self::HOOK,
                [$jobId],
                'my-ai-agent'
            );

            return;
        }

        wp_schedule_single_event(
            $timestamp,
            self::HOOK,
            [$jobId]
        );
    }
}
