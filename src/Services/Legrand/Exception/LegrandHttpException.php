<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Exception;

final class LegrandHttpException extends LegrandException
{
    public function __construct(
        string $message,
        private readonly ?int $statusCode = null
    ) {
        parent::__construct($message);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
