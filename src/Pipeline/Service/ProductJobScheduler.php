<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

final class ProductJobScheduler
{
    public const HOOK = 'my_ai_agent_run_product_job';

    public function register(): void
    {
        do_action(
            'my_ai_agent_run_product_job',
            1
        );

       /* add_action(
            self::HOOK,
            function (int $jobId): void {

                $service = \MyAIAgent\Core\Plugin::instance()
                    ->container()
                    ->get(ProductJobService::class);

                $service->run($jobId);
            }
        );*/
    }

    public function schedule(int $jobId, int $delay = 0): void
    {

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
