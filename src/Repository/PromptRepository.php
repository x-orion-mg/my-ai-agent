<?php

declare(strict_types=1);

namespace MyAIAgent\Repository;

use MyAIAgent\Prompt\Prompt;

/**
 * Data-access layer for prompts (CRUD).
 */
final class PromptRepository
{
    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'my_ai_agent_prompts';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        global $wpdb;

        $now = current_time('mysql', true);

        $wpdb->insert(
            self::tableName(),
            [
                'name'        => sanitize_text_field((string) ($data['name'] ?? '')),
                'description' => sanitize_textarea_field((string) ($data['description'] ?? '')),
                'content'     => (string) ($data['content'] ?? ''),
                'is_active'   => ! empty($data['is_active']) ? 1 : 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
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

        if (array_key_exists('name', $data)) {
            $fields['name'] = sanitize_text_field((string) $data['name']);
            $formats[]      = '%s';
        }
        if (array_key_exists('description', $data)) {
            $fields['description'] = sanitize_textarea_field((string) $data['description']);
            $formats[]             = '%s';
        }
        if (array_key_exists('content', $data)) {
            $fields['content'] = (string) $data['content'];
            $formats[]         = '%s';
        }
        if (array_key_exists('is_active', $data)) {
            $fields['is_active'] = ! empty($data['is_active']) ? 1 : 0;
            $formats[]           = '%d';
        }

        if ($fields === []) {
            return false;
        }

        $fields['updated_at'] = current_time('mysql', true);
        $formats[]            = '%s';

        return $wpdb->update(self::tableName(), $fields, ['id' => $id], $formats, ['%d']) !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;

        return $wpdb->delete(self::tableName(), ['id' => $id], ['%d']) !== false;
    }

    public function find(int $id): ?Prompt
    {
        global $wpdb;

        $table = self::tableName();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        return is_array($row) ? Prompt::fromRow($row) : null;
    }

    /**
     * @return array<int, Prompt>
     */
    public function all(bool $activeOnly = false): array
    {
        global $wpdb;

        $table = self::tableName();
        $where = $activeOnly ? 'WHERE is_active = 1' : '';

        $rows = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY name ASC", ARRAY_A) ?: [];

        return array_map([Prompt::class, 'fromRow'], $rows);
    }

    public function firstActive(): ?Prompt
    {
        $all = $this->all(true);

        return $all[0] ?? null;
    }

    public function count(): int
    {
        global $wpdb;

        $table = self::tableName();

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
}
