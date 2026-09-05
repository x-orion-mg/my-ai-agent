<?php

declare(strict_types=1);

namespace MyAIAgent\Prompt;

/**
 * Value object mirroring a row of the prompts table.
 */
final class Prompt
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $content,
        public readonly bool $isActive,
        public readonly string $createdAt = '',
        public readonly string $updatedAt = ''
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            name: (string) ($row['name'] ?? ''),
            description: (string) ($row['description'] ?? ''),
            content: (string) ($row['content'] ?? ''),
            isActive: (bool) ($row['is_active'] ?? false),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? '')
        );
    }
}
