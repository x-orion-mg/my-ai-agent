<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Repository;

use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Enum\ProductJobStatus;
use MyAIAgent\Pipeline\Enum\ProductJobType;
use wpdb;

final class ProductJobRepository
{
    public function __construct(
        private readonly ?wpdb $wpdb = null,
    ) {
    }

    private function db(): wpdb
    {
        if ($this->wpdb !== null) {
            return $this->wpdb;
        }

        global $wpdb;
        return $wpdb;
    }

    public static function tableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'my_ai_agent_legrand_product_jobs';
    }

    /**
     * Create the job only once for the import row/type pair.
     *
     * @param array<string,mixed> $payload
     */
    public function create(
        int $importRowId,
        string $reference,
        ProductJobType $type,
        array $payload = [],
        int $maxAttempts = 5,
    ): int {
        $db = $this->db();

        $existing = $this->findByImportRowAndType($importRowId, $type);
        if ($existing !== null) {
            return $existing->id;
        }

        $now = current_time('mysql', true);

        $ok = $db->insert(
            self::tableName(),
            [
                'import_row_id' => $importRowId,
                'reference' => $reference,
                'type' => $type->value,
                'status' => ProductJobStatus::PENDING->value,
                'attempts' => 0,
                'max_attempts' => max(1, $maxAttempts),
                'available_at' => $now,
                'payload' => wp_json_encode($payload),
                'last_error' => null,
                'locked_at' => null,
                'locked_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d','%s','%s','%s','%d','%d','%s','%s','%s','%s','%s','%s'
            ]
        );

        if ($ok === false) {
            $existing = $this->findByImportRowAndType($importRowId, $type);
            return $existing?->id ?? 0;
        }

        return (int) $db->insert_id;
    }

    public function find(int $id): ?ProductJob
    {
        $db = $this->db();
        $row = $db->get_row(
            $db->prepare(
                'SELECT * FROM ' . self::tableName() . ' WHERE id = %d LIMIT 1',
                $id
            ),
            ARRAY_A
        );

        return is_array($row) ? ProductJob::fromRow($row) : null;
    }

    public function findByImportRowAndType(
        int $importRowId,
        ProductJobType $type
    ): ?ProductJob {
        $db = $this->db();
        $row = $db->get_row(
            $db->prepare(
                'SELECT * FROM ' . self::tableName() .
                ' WHERE import_row_id = %d AND type = %s LIMIT 1',
                $importRowId,
                $type->value
            ),
            ARRAY_A
        );

        return is_array($row) ? ProductJob::fromRow($row) : null;
    }

    /**
     * @return array<int,ProductJob>
     */
    public function findPending(int $limit = 50): array
    {
        $db = $this->db();
        $limit = max(1, min(500, $limit));

        $rows = $db->get_results(
            $db->prepare(
                'SELECT * FROM ' . self::tableName() .
                ' WHERE status = %s AND available_at <= %s
                  ORDER BY id ASC LIMIT %d',
                ProductJobStatus::PENDING->value,
                current_time('mysql', true),
                $limit
            ),
            ARRAY_A
        ) ?: [];

        return array_map(
            static fn(array $row): ProductJob => ProductJob::fromRow($row),
            $rows
        );
    }

    public function claim(int $id, string $workerId): ?ProductJob
    {
        $db = $this->db();
        $now = current_time('mysql', true);

        $updated = $db->query(
            $db->prepare(
                'UPDATE ' . self::tableName() . '
                 SET status = %s,
                     attempts = attempts + 1,
                     locked_at = %s,
                     locked_by = %s,
                     updated_at = %s
                 WHERE id = %d
                   AND status = %s
                   AND available_at <= %s',
                ProductJobStatus::PROCESSING->value,
                $now,
                $workerId,
                $now,
                $id,
                ProductJobStatus::PENDING->value,
                $now
            )
        );

        if ($updated !== 1) {
            return null;
        }

        return $this->find($id);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function complete(int $id, array $payload = []): bool
    {
        return $this->updateState(
            $id,
            ProductJobStatus::COMPLETED,
            $payload,
            null,
            null
        );
    }

    public function retry(
        int $id,
        int $delay,
        string $error
    ): bool {
        $db = $this->db();
        $availableAt = gmdate('Y-m-d H:i:s', time() + max(0, $delay));

        return $db->update(
            self::tableName(),
            [
                'status' => ProductJobStatus::PENDING->value,
                'available_at' => $availableAt,
                'last_error' => $error,
                'locked_at' => null,
                'locked_by' => null,
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $id],
            ['%s','%s','%s','%s','%s','%s'],
            ['%d']
        ) !== false;
    }

    public function fail(int $id, string $error, bool $dead = false): bool
    {
        return $this->updateState(
            $id,
            $dead ? ProductJobStatus::DEAD : ProductJobStatus::FAILED,
            [],
            $error,
            null
        );
    }

    public function recoverStale(int $timeoutSeconds = 900): int
    {
        $db = $this->db();
        $threshold = gmdate('Y-m-d H:i:s', time() - max(60, $timeoutSeconds));

        return (int) $db->query(
            $db->prepare(
                'UPDATE ' . self::tableName() . '
                 SET status = %s,
                     available_at = %s,
                     locked_at = NULL,
                     locked_by = NULL,
                     updated_at = %s
                 WHERE status = %s
                   AND locked_at IS NOT NULL
                   AND locked_at < %s',
                ProductJobStatus::PENDING->value,
                current_time('mysql', true),
                current_time('mysql', true),
                ProductJobStatus::PROCESSING->value,
                $threshold
            )
        );
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function updateState(
        int $id,
        ProductJobStatus $status,
        array $payload,
        ?string $error,
        mixed $unused,
    ): bool {
        $data = [
            'status' => $status->value,
            'locked_at' => null,
            'locked_by' => null,
            'updated_at' => current_time('mysql', true),
        ];
        $formats = ['%s','%s','%s','%s'];

        if ($payload !== []) {
            $data['payload'] = wp_json_encode($payload);
            $formats[] = '%s';
        }

        if ($error !== null) {
            $data['last_error'] = $error;
            $formats[] = '%s';
        }

        return $this->db()->update(
            self::tableName(),
            $data,
            ['id' => $id],
            $formats,
            ['%d']
        ) !== false;
    }
}
