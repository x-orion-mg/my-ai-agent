<?php

declare(strict_types=1);

namespace MyAIAgent\History;

/**
 * Value object mirroring a row of the history table.
 */
final class HistoryEntry
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $provider,
        public readonly int $promptId,
        public readonly string $status,
        public readonly float $duration,
        public readonly string $message,
        public readonly string $createdAt
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            productId: (int) ($row['product_id'] ?? 0),
            provider: (string) ($row['provider'] ?? ''),
            promptId: (int) ($row['prompt_id'] ?? 0),
            status: (string) ($row['status'] ?? ''),
            duration: (float) ($row['duration'] ?? 0),
            message: (string) ($row['message'] ?? ''),
            createdAt: (string) ($row['created_at'] ?? '')
        );
    }
}
