<?php

declare(strict_types=1);

namespace MyAIAgent\API;

/**
 * Value object mirroring a row of the API keys table.
 */
final class ApiKey
{
    public function __construct(
        public readonly int $id,
        public readonly string $provider,
        public readonly string $label,
        public readonly string $apiKey,
        public readonly string $model,
        public readonly string $endpoint,
        public readonly int $priority,
        public readonly bool $isActive,
        public readonly int $errorCount,
        public readonly ?string $lastUsedAt,
        public readonly string $createdAt = ''
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            provider: (string) ($row['provider'] ?? ''),
            label: (string) ($row['label'] ?? ''),
            apiKey: (string) ($row['api_key'] ?? ''),
            model: (string) ($row['model'] ?? ''),
            endpoint: (string) ($row['endpoint'] ?? ''),
            priority: (int) ($row['priority'] ?? 10),
            isActive: (bool) ($row['is_active'] ?? false),
            errorCount: (int) ($row['error_count'] ?? 0),
            lastUsedAt: isset($row['last_used_at']) ? (string) $row['last_used_at'] : null,
            createdAt: (string) ($row['created_at'] ?? '')
        );
    }

    /**
     * Masked representation of the key for display in the admin UI.
     */
    public function maskedKey(): string
    {
        $length = strlen($this->apiKey);

        if ($length <= 4) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', max(0, $length - 4)) . substr($this->apiKey, -4);
    }
}
