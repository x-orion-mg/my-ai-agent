<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\DTO;

use MyAIAgent\Pipeline\Enum\ProductJobStatus;
use MyAIAgent\Pipeline\Enum\ProductJobType;

final readonly class ProductJob
{
    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        public int $id,
        public int $importRowId,
        public string $reference,
        public ProductJobType $type,
        public ProductJobStatus $status,
        public int $attempts,
        public int $maxAttempts,
        public ?string $availableAt,
        public array $payload,
    ) {
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $payload = [];
        if (isset($row['payload']) && is_string($row['payload']) && $row['payload'] !== '') {
            $decoded = json_decode($row['payload'], true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        return new self(
            id: (int) $row['id'],
            importRowId: (int) $row['import_row_id'],
            reference: (string) $row['reference'],
            type: ProductJobType::from((string) $row['type']),
            status: ProductJobStatus::from((string) $row['status']),
            attempts: (int) $row['attempts'],
            maxAttempts: (int) $row['max_attempts'],
            availableAt: isset($row['available_at']) ? (string) $row['available_at'] : null,
            payload: $payload,
        );
    }
}
