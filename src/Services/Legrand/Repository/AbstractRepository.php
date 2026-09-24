<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Repository;

use wpdb;

abstract class AbstractRepository
{
    protected wpdb $wpdb;

    public function __construct(?wpdb $wpdb = null)
    {
        if ($wpdb !== null) {
            $this->wpdb = $wpdb;
            return;
        }

        global $wpdb;
        $this->wpdb = $wpdb;
    }

    abstract protected static function tableSuffix(): string;

    public static function tableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'my_ai_agent_legrand_' . static::tableSuffix();
    }

    public function find(int $id): ?array
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM " . static::tableName() . " WHERE id = %d LIMIT 1", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function count(): int
    {
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM " . static::tableName());
    }

    public function insert(array $data, array $format = []): int
    {
        $result = $this->wpdb->insert(static::tableName(), $data, $format);
        return $result === false ? 0 : (int) $this->wpdb->insert_id;
    }

    public function updateById(int $id, array $data, array $format = []): bool
    {
        return $this->wpdb->update(static::tableName(), $data, ['id' => $id], $format, ['%d']) !== false;
    }

    public function deleteById(int $id): bool
    {
        return $this->wpdb->delete(static::tableName(), ['id' => $id], ['%d']) !== false;
    }

    protected function getRow(string $sql, array $args = []): ?array
    {
        $query = $args ? $this->wpdb->prepare($sql, ...$args) : $sql;
        $row = $this->wpdb->get_row($query, ARRAY_A);
        return $row ?: null;
    }

    protected function getResults(string $sql, array $args = []): array
    {
        $query = $args ? $this->wpdb->prepare($sql, ...$args) : $sql;
        return $this->wpdb->get_results($query, ARRAY_A) ?: [];
    }
}
