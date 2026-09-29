<?php


declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use InvalidArgumentException;
use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Enum\ProductJobStatus;
use MyAIAgent\Pipeline\Enum\ProductJobType;
use MyAIAgent\Pipeline\Handler\WooCommerceJobHandler;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use RuntimeException;

/**
 * Exécute les handlers du pipeline en mémoire pour les tests manuels.
 *
 * IMPORTANT :
 *
 * - aucun product_job n'est créé ;
 * - aucun job n'est modifié ;
 * - aucun job suivant n'est programmé ;
 * - aucun cron n'est déclenché.
 */
final readonly class PipelineTestService
{
    /**
     * @param array<string,JobHandlerInterface> $handlers
     */
    public function __construct(
        private ImportRowRepository $rows,
        private array               $handlers,
    )
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function testRow(
        int            $importRowId,
        ProductJobType $target,
        bool           $executeWooCommerce = false,
    ): array
    {
        $row = $this->rows->find($importRowId);

        if ($row === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Import row #%d introuvable.',
                    $importRowId
                )
            );
        }

        $reference = trim(
            (string)($row['reference'] ?? '')
        );

        if ($reference === '') {
            throw new RuntimeException(
                sprintf(
                    'Import row #%d ne contient aucune référence.',
                    $importRowId
                )
            );
        }

        /*
         * Le payload initial correspond exactement
         * à celui utilisé par PipelineDispatcher.
         */
        $payload = [
            'import_row' => $row,
        ];

        $results = [];

        /*
         * Chaque étape dépend des précédentes.
         *
         * SOURCE
         *
         * SOURCE + AI
         *
         * SOURCE + AI + VALIDATION
         *
         * SOURCE + AI + VALIDATION + WOOCOMMERCE
         */
        foreach ($this->sequenceUntil($target) as $type) {

            $job = new ProductJob(
                id: 0,
                importRowId: $importRowId,
                reference: $reference,
                type: $type,
                status: ProductJobStatus::PENDING,
                attempts: 0,
                maxAttempts: 1,
                availableAt: null,
                payload: $payload,
            );

            $handler = $this->handlers[$type->value] ?? null;

            if (!$handler instanceof JobHandlerInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Aucun handler configuré pour %s.',
                        $type->value
                    )
                );
            }

            /*
             * WooCommerce :
             *
             * Par défaut on ne crée/modifie PAS le produit.
             *
             * Il faut explicitement utiliser --execute.
             */
            if (
                $type === ProductJobType::WOOCOMMERCE
                && !$executeWooCommerce
            ) {
                if (!$handler instanceof WooCommerceJobHandler) {
                    throw new RuntimeException(
                        'Le handler WooCommerce ne permet pas le mode dry-run.'
                    );
                }

                $prepared = $handler->prepare($job);

                $results[$type->value] = [
                    'status' => 'DRY_RUN',
                    'data' => $prepared,
                    'message' => 'WooCommerce non exécuté. Utilisez --execute pour créer/modifier le produit.',
                ];

                $payload = array_replace(
                    $payload,
                    [
                        'woocommerce_test' => $prepared,
                    ]
                );

                break;
            }

            /*
             * Exécution réelle du handler.
             */
            $data = $handler->handle($job);

            /*
             * Le résultat devient le payload
             * de l'étape suivante.
             */
            $payload = array_replace(
                $payload,
                $data
            );

            $results[$type->value] = [
                'status' => 'OK',
                'data' => $data,
            ];
        }

        return [
            'import_row_id' => $importRowId,
            'reference' => $reference,
            'target' => $target->value,
            'results' => $results,
            'payload' => $payload,
        ];
    }

    /**
     * @return list<ProductJobType>
     */
    private function sequenceUntil(
        ProductJobType $target
    ): array
    {
        $sequence = [
            ProductJobType::SOURCE,
            ProductJobType::AI_GENERATION,
            ProductJobType::VALIDATION,
            ProductJobType::WOOCOMMERCE,
        ];

        $index = array_search(
            $target,
            $sequence,
            true
        );

        if ($index === false) {
            throw new InvalidArgumentException(
                'Type de job inconnu.'
            );
        }

        return array_slice(
            $sequence,
            0,
            $index + 1
        );
    }
}