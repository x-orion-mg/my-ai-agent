<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Legrand\Product\LegrandProductService;
use Throwable;

final class SourceJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly LegrandProductService $legrand,
    ) {
    }

    public function handle(ProductJob $job): array
    {
        try {
            $product = $this->legrand->getProduct($job->reference);
        } catch (Throwable $exception) {
            throw new RetryableJobException(
                'Erreur pendant la récupération LEGRAND : ' . $exception->getMessage(),
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

        if ($product->url === null && $product->image === null && $product->technicalData === null) {
            throw new PermanentJobException(
                sprintf(
                    'Aucune donnée officielle récupérée pour "%s".',
                    $job->reference
                )
            );
        }

        return [
            'source' => $product->toArray(),
        ];
    }
}
