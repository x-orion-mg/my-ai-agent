<?php

declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Legrand\Product\LegrandProductService;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use Throwable;

final class SourceJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly LegrandProductService $legrand,
        private readonly ImportRowRepository $importRows,
    ) {
    }

    public function handle(
        ProductJob $job
    ): array {
        /*
         * ======================================================
         * 1. VERIFICATION DU CACHE DB
         * ======================================================
         */

        $cachedSource = $this->importRows->getCachedSource(
            $job->importRowId
        );

        if ($cachedSource !== null) {
            /*
             * La source LEGRAND existe déjà en DB.
             *
             * Aucun appel HTTP.
             * Aucun scraping.
             */
            return [
                'source' => $cachedSource,
            ];
        }

        /*
         * ======================================================
         * 2. RECUPERATION LEGRAND
         * ======================================================
         */

        try {
            $product = $this->legrand->getProduct(
                $job->reference
            );
        } catch (Throwable $exception) {
            throw new RetryableJobException(
                'Erreur pendant la récupération LEGRAND : '
                . $exception->getMessage(),
                previous: $exception
            );
        }

        if ($product === null) {
            throw new PermanentJobException(
                sprintf(
                    'Produit LEGRAND introuvable pour la référence "%s".',
                    $job->reference
                )
            );
        }

        if (
            $product->url === null
            && $product->image === null
            && $product->technicalData === null
        ) {
            throw new PermanentJobException(
                sprintf(
                    'Aucune donnée officielle récupérée pour "%s".',
                    $job->reference
                )
            );
        }

        /*
         * ======================================================
         * 3. TRANSFORMATION
         * ======================================================
         */

        $source = $product->toArray();

        /*
         * ======================================================
         * 4. SAUVEGARDE DANS import_rows
         * ======================================================
         */

        $saved = $this->importRows->saveSourceData(
            $job->importRowId,
            $source
        );

        if (!$saved) {
            throw new RetryableJobException(
                sprintf(
                    'Impossible de sauvegarder les données LEGRAND '
                    . 'pour la référence "%s".',
                    $job->reference
                )
            );
        }

        /*
         * ======================================================
         * 5. TRANSMISSION AU JOB SUIVANT
         * ======================================================
         */

        return [
            'source' => $source,
        ];
    }
}
