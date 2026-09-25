<?php


declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand\Response;

final readonly class LegrandCsvValidationResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public bool  $valid,
        public int   $totalRows,
        public array $errors = []
    )
    {
    }

    public function errorCount(): int
    {
        return count($this->errors);
    }

    public function errorMessage(): string
    {
        return implode("\n", $this->errors);
    }
}