<?php

declare(strict_types=1);

namespace MyAIAgent\Repository;

use MyAIAgent\Execution\Execution;
use MyAIAgent\Execution\ExecutionStatus;

final class ExecutionRepository
{
    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'my_ai_agent_executions';
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(
        string $uuid,
        string $agentId,
        int $userId,
        array $input
    ): int {
        global $wpdb;

        $now = current_time('mysql', true);

        $result = $wpdb->insert(
            self::tableName(),
            [
                'uuid'         => $uuid,
                'agent_id'     => $agentId,
                'user_id'      => $userId,
                'status'       => ExecutionStatus::PENDING,
                'current_step' => 0,
                'input'        => wp_json_encode($input),
                'data'         => wp_json_encode([]),
                'error'        => null,
                'created_at'   => $now,
                'updated_at'   => $now,
                'completed_at' => null,
            ],
            [
                '%s',
                '%s',
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public function findByUuid(string $uuid): ?Execution
    {
        global $wpdb;

        $table = self::tableName();

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE uuid = %s LIMIT 1",
                $uuid
            ),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function update(Execution $execution): bool
    {
        global $wpdb;

        $now = current_time('mysql', true);

        $data = [
            'status'       => $execution->status(),
            'current_step' => $execution->currentStep(),
            'data'         => wp_json_encode($execution->data()),
            'error'        => $execution->error(),
            'updated_at'   => $now,
        ];

        $formats = [
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
        ];

        if ($execution->isFinished()) {
            $data['completed_at'] = $now;
            $formats[] = '%s';
        }

        $result = $wpdb->update(
            self::tableName(),
            $data,
            [
                'uuid' => $execution->id(),
            ],
            $formats,
            ['%s']
        );

        return $result !== false;
    }

    public function delete(string $uuid): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            self::tableName(),
            [
                'uuid' => $uuid,
            ],
            ['%s']
        );

        return $result !== false;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Execution
    {
        return new Execution(
            id: (string) $row['uuid'],
            agentId: (string) $row['agent_id'],
            input: $this->decodeArray($row['input'] ?? null),
            status: (string) $row['status'],
            currentStep: (int) $row['current_step'],
            data: $this->decodeArray($row['data'] ?? null),
            error: $row['error'] !== null
                ? (string) $row['error']
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeArray(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }
}
