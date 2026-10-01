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
    public function dispatchImport(
        int $importId,
        int $batchSize = 50
    ): int {
        $import = $this->imports->find($importId);

        if ($import === null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Import #%d introuvable.',
                    $importId
                )
            );
        }

        /*
         * Limite de sécurité :
         *
         * minimum : 1
         * maximum : 500
         */
        $batchSize = max(
            1,
            min(500, $batchSize)
        );

        $wpdb = $this->db();

        $count = 0;

        /*
         * On traite les lignes par batch jusqu'à ce qu'il
         * n'y ait plus aucune ligne à dispatcher.
         */
        while (true) {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT *
                 FROM ' . ImportRowRepository::tableName() . '
                 WHERE import_id = %d
                   AND status = %s
                 ORDER BY id ASC
                 LIMIT %d',
                    $importId,
                    'pending',
                    $batchSize
                ),
                ARRAY_A
            ) ?: [];

            /*
             * Plus aucune ligne à traiter.
             */
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                $rowId = (int)($row['id'] ?? 0);
                $reference = trim(
                    (string)($row['reference'] ?? '')
                );

                /*
                 * Ligne invalide.
                 */
                if (
                    $rowId <= 0
                    || $reference === ''
                ) {
                    continue;
                }

                /*
                 * On crée le job.
                 */
                $jobId = $this->jobs->create(
                    $rowId,
                    $reference,
                    ProductJobType::SOURCE,
                    [
                        'import_row' => $row,
                    ]
                );

                if ($jobId <= 0) {
                    continue;
                }

                /*
                 * On programme le job.
                 */
                $this->scheduler->schedule(
                    $jobId
                );

                /*
                 * Très important :
                 * la ligne ne doit plus être sélectionnée
                 * au prochain batch.
                 */
                $wpdb->update(
                    ImportRowRepository::tableName(),
                    [
                        'status' => 'processing',
                    ],
                    [
                        'id' => $rowId,
                    ],
                    [
                        '%s',
                    ],
                    [
                        '%d',
                    ]
                );

                ++$count;
            }

            /*
             * Sécurité supplémentaire :
             *
             * Si aucun job n'a été créé dans ce batch,
             * on arrête pour éviter une boucle infinie.
             */
            if ($count === 0) {
                break;
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
