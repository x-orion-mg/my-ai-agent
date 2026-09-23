<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Product;

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use  MyAIAgent\Services\Legrand\Support\LegrandReference;

final class LegrandProductService
{
    public function __construct(
        private readonly LegrandHttpClient $http,
        private readonly ProductUrlExtractor $urlExtractor,
        private readonly ProductImageExtractor $imageExtractor,
        private readonly ProductTechnicalDataExtractor $technicalDataExtractor,
    ) {
    }

    public function getProduct(
        string $reference
    ): ?LegrandProduct {
        $reference = LegrandReference::normalize(
            $reference
        );

        if ($reference === '') {
            return null;
        }

        /*
         * ======================================================
         * 1. PAGE PRODUIT
         * ======================================================
         */
        $url = $this->urlExtractor->find(
            $reference
        );

        if ($url === null) {
            return null;
        }

        /*
         * ======================================================
         * 2. HTML PRODUIT
         * ======================================================
         */
        $html = $this->http->get($url);

        /*
         * ======================================================
         * 3. IMAGE
         * ======================================================
         */
        $image = $this->imageExtractor->extract(
            $html,
            $reference
        );

        /*
         * ======================================================
         * 4. FALLBACK PIM
         * ======================================================
         */
        if ($image === null) {
            $image = $this->imageExtractor->findDirectPim(
                $reference
            );
        }

        /*
         * ======================================================
         * 5. CARACTÉRISTIQUES
         * ======================================================
         */
        $technicalData =
            $this->technicalDataExtractor->extract(
                $html
            );

        /*
         * ======================================================
         * 6. OBJET FINAL
         * ======================================================
         */
        return new LegrandProduct(
            reference: $reference,
            url: $url,
            image: $image,
            technicalData: $technicalData,
        );
    }
}