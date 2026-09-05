<?php


declare(strict_types=1);

namespace MyAIAgent\Agent;

final class StepResult
{
    public const CONTINUE = 'continue';

    public const WAITING_HUMAN = 'waiting_human';

    public const FAILED = 'failed';

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly string  $status,
        private readonly array   $data = [],
        private readonly ?string $message = null,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function shouldContinue(): bool
    {
        return $this->status === self::CONTINUE;
    }

    public function isWaitingHuman(): bool
    {
        return $this->status === self::WAITING_HUMAN;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function continue(
        array $data = []
    ): self
    {
        return new self(
            self::CONTINUE,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function waitingHuman(
        array   $data = [],
        ?string $message = null
    ): self
    {
        return new self(
            self::WAITING_HUMAN,
            $data,
            $message
        );
    }

    public static function failed(
        string $message
    ): self
    {
        return new self(
            self::FAILED,
            [],
            $message
        );
    }
}
