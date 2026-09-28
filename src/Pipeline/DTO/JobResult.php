<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\DTO;

final readonly class JobResult
{
    /**
     * @param array<string,mixed> $data
     */
    public function __construct(
        public array $data = [],
    ) {
    }
}
