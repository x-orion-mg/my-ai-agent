<?php

declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use MyAIAgent\Services\Product\ProductService;
use Throwable;

final class WooCommerceJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly ImportRowRepository $importRows,
    ) {
    }

    /**
     * Prépare les données qui seront envoyées à WooCommerce.
     *
     * IMPORTANT :
     * Cette méthode ne crée/modifie aucun produit.
     *
     * @return array<string,mixed>
     */
    public function prepare(
        ProductJob $job
    ): array {
        $data = $job->payload['data'] ?? null;

        $source = $job->payload['source'] ?? [];

        if (!is_array($data)) {
            throw new PermanentJobException(
                'Les données produit validées sont absentes.'
            );
        }

        if (!is_array($source)) {
            $source = [];
        }

        /*
         * Le SKU officiel est toujours
         * la référence importée.
         */
        $data['sku'] = $job->reference;

        /*
         * Marque par défaut.
         */
        if (
            !isset($data['brand'])
            || !is_string($data['brand'])
            || trim($data['brand']) === ''
        ) {
            $data['brand'] = 'Legrand';
        }

        /*
         * Données provenant de la source officielle.
         */
        if (isset($source['url'])) {
            $data['url'] = $source['url'];
        }

        if (isset($source['image'])) {
            $data['image_url'] = $source['image'];
        }

        if (isset($source['technicalData'])) {
            $data['technicalData'] = $source['technicalData'];
        }

        return $data;
    }

    /**
     * Exécution réelle WooCommerce.
     */
    public function handle(
        ProductJob $job
    ): array {
        try {
            $productId = $this->productService->createOrUpdate(
                $this->prepare($job)
            );
        } catch (Throwable $exception) {
            throw new RetryableJobException(
                'Erreur WooCommerce : ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if ($productId <= 0) {
            throw new RetryableJobException(
                'WooCommerce n’a pas retourné un identifiant valide.'
            );
        }
        $updated = $this->importRows->updateStatus(
            $job->reference,
            ImportRowRepository::STATUS_COMPLETED
        );

        if (!$updated) {
            error_log(
                sprintf(
                    '[MY-AI-AGENT] Produit WooCommerce créé (%d), '
                    . 'mais impossible de mettre import_rows #%d à completed.',
                    $productId,
                    $job->reference
                )
            );
        }
        return [
            'woocommerce' => [
                'product_id' => $productId,
                'sku' => $job->reference,
            ],
        ];
    }
}