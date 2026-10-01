<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Product;

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use MyAIAgent\Services\Legrand\Exception\ProductNotFoundException;
use  MyAIAgent\Services\Legrand\Support\LegrandReference;

final class LegrandProductService
{
    public function __construct(
        private readonly LegrandHttpClient $http,
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
        $url = $this->http->legrandScrapSearchProduct($reference);

        if ($url === null) {
            throw new ProductNotFoundException(
                'Impossible de trouver la page produit Legrand pour une référence vide.'
            );
        }

        /*
         * ======================================================
         * 2. HTML PRODUIT
         * ======================================================
         */
        return $this->http->legrandScrap($url, $reference);
    }
}