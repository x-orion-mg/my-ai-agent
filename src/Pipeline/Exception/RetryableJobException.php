<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Exception;

use RuntimeException;

class RetryableJobException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $retryAfter = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
