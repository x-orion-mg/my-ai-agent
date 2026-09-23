<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Product;

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use  MyAIAgent\Services\Legrand\Support\LegrandReference;
use MyAIAgent\Services\Legrand\Support\LegrandUrl;

final class LegrandProductService
{
    public function __construct(
        private readonly LegrandHttpClient $http,
        private readonly ProductUrlExtractor $urlExtractor,
        private readonly ProductImageExtractor $imageExtractor,
        private readonly ProductTechnicalDataExtractor $technicalDataExtractor,
        private readonly LegrandUrl $legrandUrl
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
         * 1. PAGE PRODUIT MG
         * ======================================================
         */
        $url = $this->urlExtractor->find($reference);

        if ($url === null) {
            return null;
        }

        /*
         * ======================================================
         * 2. HTML PRODUIT
         * ======================================================
         */
        $url = $this->legrandUrl->findLegrandProductPage($reference);
        $html = $this->http->getEn($url);

        /*
         * ======================================================
         * 3. IMAGE
         * ======================================================
         */
        $image = $this->imageExtractor->extract($html, $reference);

        /*
         * ======================================================
         * 5. CARACTÉRISTIQUES
         * ======================================================
         */
        $technicalData = $this->technicalDataExtractor->extract($html);

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