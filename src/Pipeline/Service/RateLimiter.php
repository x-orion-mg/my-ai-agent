<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

final class RateLimiter
{
    /**
     * @var array<string,int>
     */
    private array $lastExecution = [];

    public function wait(string $bucket, int $minimumIntervalSeconds): void
    {
        $minimumIntervalSeconds = max(0, $minimumIntervalSeconds);

        if ($minimumIntervalSeconds === 0) {
            return;
        }

        $now = time();
        $last = $this->lastExecution[$bucket] ?? 0;
        $remaining = ($last + $minimumIntervalSeconds) - $now;

        if ($remaining > 0) {
            sleep($remaining);
        }

        $this->lastExecution[$bucket] = time();
    }
}
