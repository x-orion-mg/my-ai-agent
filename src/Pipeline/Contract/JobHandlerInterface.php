<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Contract;

use MyAIAgent\Pipeline\DTO\ProductJob;

interface JobHandlerInterface
{
    /**
     * @return array<string,mixed> Data to merge into the job payload.
     */
    public function handle(ProductJob $job): array;
}
