<?php


declare(strict_types=1);

namespace MyAIAgent\Agent\Response;

interface AiResponseValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws AiResponseValidationException
     */
    public function validate(array $data): void;
}
