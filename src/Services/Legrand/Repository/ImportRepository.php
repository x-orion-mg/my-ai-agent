<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Repository;

final class ImportRepository extends AbstractRepository
{
    protected static function tableSuffix(): string
    {
        return 'imports';
    }

    public function create( string $source, string $filename, int $totalRows): int {
        $result = $this->wpdb->insert(
            self::tableName(),
            [
                'source'     => $source,
                'filename'   => $filename,
                'status'     => 'uploaded',
                'started_at' => current_time('mysql'),
                'total_rows' => $totalRows,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
            ]
        );

        return $result === false
            ? 0
            : (int) $this->wpdb->insert_id;
    }

    public function markUploaded( int $id, string $filename, int $totalRows): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'filename'     => $filename,
                    'status'       => 'uploaded',
                    'total_rows'   => $totalRows,
                    'finished_at'  => current_time('mysql'),
                ],
                [
                    'id' => $id,
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            ) !== false;
    }

    public function markValidationFailed( int $id, string $message, int $errorCount ): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'status'        => 'validation_failed',
                    'error_message' => $message,
                    'errors'        => $errorCount,
                    'finished_at'   => current_time('mysql'),
                ],
                [
                    'id' => $id,
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            ) !== false;
    }

    public function markError( int $id, string $message): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'status'        => 'error',
                    'error_message' => $message,
                    'errors'        => 1,
                    'finished_at'   => current_time('mysql'),
                ],
                [
                    'id' => $id,
                ],
                [
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                ],
                [
                    '%d',
                ]
            ) !== false;
    }

    public function find(int $id): ?array
    {
        return $this->getRow(
            'SELECT * FROM ' . self::tableName() . ' WHERE id = %d LIMIT 1',
            [$id]
        );
    }

    public function count(): int
    {
        return (int) $this->wpdb->get_var(
            'SELECT COUNT(*) FROM ' . self::tableName()
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        return $this->wpdb->get_results(
            'SELECT *
         FROM ' . self::tableName() . '
         ORDER BY id DESC',
            ARRAY_A
        );
    }


}
