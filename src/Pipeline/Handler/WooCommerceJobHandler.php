<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Product\ProductService;
use Throwable;

final class WooCommerceJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
    }

    public function handle(ProductJob $job): array
    {
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

        $data['sku'] = $job->reference;

        if (!isset($data['brand']) || !is_string($data['brand']) || trim($data['brand']) === '') {
            $data['brand'] = 'Legrand';
        }

        if (isset($source['url'])) {
            $data['url'] = $source['url'];
        }

        if (isset($source['image'])) {
            $data['image_url'] = $source['image'];
        }

        if (isset($source['technicalData'])) {
            $data['technicalData'] = $source['technicalData'];
        }

        try {
            $productId = $this->productService->createOrUpdate($data);
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

        return [
            'woocommerce' => [
                'product_id' => $productId,
                'sku' => $job->reference,
            ],
        ];
    }
}
