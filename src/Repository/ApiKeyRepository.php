<?php

declare(strict_types=1);

namespace MyAIAgent\Repository;

use MyAIAgent\API\ApiKey;

/**
 * Data-access layer for AI provider API keys (CRUD + error/usage bookkeeping).
 */
final class ApiKeyRepository
{
    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'my_ai_agent_api_keys';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            self::tableName(),
            [
                'provider'    => sanitize_key((string) ($data['provider'] ?? '')),
                'label'       => sanitize_text_field((string) ($data['label'] ?? '')),
                'api_key'     => (string) ($data['api_key'] ?? ''),
                'model'       => sanitize_text_field((string) ($data['model'] ?? '')),
                'endpoint'    => esc_url_raw((string) ($data['endpoint'] ?? '')),
                'priority'    => (int) ($data['priority'] ?? 10),
                'is_active'   => ! empty($data['is_active']) ? 1 : 0,
                'error_count' => 0,
                'created_at'  => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        global $wpdb;

        $fields  = [];
        $formats = [];

        $map = [
            'provider' => '%s',
            'label'    => '%s',
            'api_key'  => '%s',
            'model'    => '%s',
            'endpoint' => '%s',
            'priority' => '%d',
            'is_active'=> '%d',
        ];

        foreach ($map as $key => $format) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $value = match ($key) {
                'provider'  => sanitize_key((string) $value),
                'label'     => sanitize_text_field((string) $value),
                'model'     => sanitize_text_field((string) $value),
                'endpoint'  => esc_url_raw((string) $value),
                'priority'  => (int) $value,
                'is_active' => ! empty($value) ? 1 : 0,
                default     => (string) $value,
            };

            $fields[$key] = $value;
            $formats[]    = $format;
        }

        if ($fields === []) {
            return false;
        }

        return $wpdb->update(self::tableName(), $fields, ['id' => $id], $formats, ['%d']) !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;

        return $wpdb->delete(self::tableName(), ['id' => $id], ['%d']) !== false;
    }

    public function find(int $id): ?ApiKey
    {
        global $wpdb;

        $table = self::tableName();
        $row   = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);

        return is_array($row) ? ApiKey::fromRow($row) : null;
    }

    /**
     * @return array<int, ApiKey>
     */
    public function all(): array
    {
        global $wpdb;

        $table = self::tableName();
        $rows  = $wpdb->get_results("SELECT * FROM {$table} ORDER BY provider ASC, priority ASC", ARRAY_A) ?: [];

        return array_map([ApiKey::class, 'fromRow'], $rows);
    }

    /**
     * Active keys for a provider ordered by priority then fewest errors.
     *
     * @return array<int, ApiKey>
     */
    public function activeForProvider(string $provider): array
    {
        global $wpdb;

        $table = self::tableName();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE provider = %s AND is_active = 1 ORDER BY priority ASC, error_count ASC, last_used_at ASC",
                $provider
            ),
            ARRAY_A
        ) ?: [];

        return array_map([ApiKey::class, 'fromRow'], $rows);
    }

    public function markUsed(int $id): void
    {
        global $wpdb;

        $wpdb->update(
            self::tableName(),
            ['last_used_at' => current_time('mysql', true)],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    public function incrementError(int $id, int $disableThreshold = 0): void
    {
        global $wpdb;

        $table = self::tableName();
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET error_count = error_count + 1 WHERE id = %d", $id));

        if ($disableThreshold > 0) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET is_active = 0 WHERE id = %d AND error_count >= %d",
                    $id,
                    $disableThreshold
                )
            );
        }
    }

    public function resetErrors(int $id): void
    {
        global $wpdb;

        $wpdb->update(self::tableName(), ['error_count' => 0], ['id' => $id], ['%d'], ['%d']);
    }
}
