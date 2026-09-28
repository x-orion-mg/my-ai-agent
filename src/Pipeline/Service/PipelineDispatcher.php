<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use MyAIAgent\Pipeline\Enum\ProductJobType;
use MyAIAgent\Pipeline\Repository\ProductJobRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;

final class PipelineDispatcher
{
    public function __construct(
        private readonly ImportRepository $imports,
        private readonly ImportRowRepository $rows,
        private readonly ProductJobRepository $jobs,
        private readonly ProductJobScheduler $scheduler,
    ) {
    }

    /**
     * Creates source jobs from existing import_rows.
     *
     * The CSV is never read here.
     */
    public function dispatchImport(int $importId, int $batchSize = 50): int
    {
        $import = $this->imports->find($importId);

        if ($import === null) {
            throw new \InvalidArgumentException(
                sprintf('Import #%d introuvable.', $importId)
            );
        }

        $batchSize = max(1, min(500, $batchSize));
        $wpdb = $this->db();

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT *
                 FROM ' . ImportRowRepository::tableName() . '
                 WHERE import_id = %d
                   AND status IN ("pending", "processing")
                 ORDER BY id ASC
                 LIMIT %d',
                $importId,
                $batchSize
            ),
            ARRAY_A
        ) ?: [];

        $count = 0;

        foreach ($rows as $row) {
            $rowId = (int) ($row['id'] ?? 0);
            $reference = trim((string) ($row['reference'] ?? ''));

            if ($rowId <= 0 || $reference === '') {
                continue;
            }

            $jobId = $this->jobs->create(
                $rowId,
                $reference,
                ProductJobType::SOURCE,
                [
                    'import_row' => $row,
                ]
            );

            if ($jobId > 0) {
                $this->scheduler->schedule($jobId);
                ++$count;
            }
        }

        return $count;
    }

    private function db(): \wpdb
    {
        global $wpdb;
        return $wpdb;
    }
}
