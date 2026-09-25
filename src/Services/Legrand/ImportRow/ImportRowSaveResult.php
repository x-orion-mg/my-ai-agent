<?php
declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\ImportRow;
final readonly class ImportRowSaveResult
{
    public function __construct(
        public int $id,
        public bool $inserted,
        public bool $updated,
    ) {
    }
}
