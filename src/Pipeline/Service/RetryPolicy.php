<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

final class RetryPolicy
{
    public function __construct(
        private readonly int $baseDelay = 60,
        private readonly int $maxDelay = 3600,
    ) {
    }

    public function delay(int $attempt, int $retryAfter = 0): int
    {
        if ($retryAfter > 0) {
            return min($retryAfter, $this->maxDelay);
        }

        $attempt = max(1, $attempt);
        $delay = $this->baseDelay * (2 ** ($attempt - 1));

        return min($delay, $this->maxDelay);
    }
}
