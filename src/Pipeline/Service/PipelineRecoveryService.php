<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use MyAIAgent\Pipeline\Repository\ProductJobRepository;

final class PipelineRecoveryService
{
    public function __construct(
        private readonly ProductJobRepository $jobs,
        private readonly ProductJobScheduler $scheduler,
    ) {
    }

    public function recover(int $timeoutSeconds = 900): int
    {

        $this->jobs->recoverStale($timeoutSeconds);

        $jobs = $this->jobs->findPending(100);

        foreach ($jobs as $job) {
            $this->scheduler->schedule($job->id);
        }

        return count($jobs);
    }
}
