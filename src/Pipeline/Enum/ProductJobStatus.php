<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Enum;

enum ProductJobStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case DEAD = 'dead';
}
