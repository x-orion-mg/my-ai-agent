<?php


declare(strict_types=1);

namespace MyAIAgent\Execution;

final class ExecutionStatus
{
    public const PENDING = 'pending';

    public const RUNNING = 'running';

    public const WAITING_HUMAN = 'waiting_human';

    public const COMPLETED = 'completed';

    public const FAILED = 'failed';

    public const CANCELLED = 'cancelled';

    private function __construct()
    {
    }

    public static function isValid(string $status): bool
    {
        return in_array(
            $status,
            [
                self::PENDING,
                self::RUNNING,
                self::WAITING_HUMAN,
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
            ],
            true
        );
    }
}
