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
        private readonly LegrandReference $support,
    ) {
    }

    public function getProduct(
        string $reference
    ): ?LegrandProduct {
        $reference = $this->support->normalize(
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
        /*
         * ======================================================
         * 2. HTML PRODUIT
         * ======================================================
         */
        if ($url !== null) {
            return $this->http->legrandScrap($url, $reference);
        }
        /*
         * Le produit n'a pas été trouvé sur le site Legrand.
         * On tente maintenant une recherche dans Spider.
         */
        $article = $this->http->spiderGetArticle($reference);

        if ($article !== null) {
            return $this->support->buildProductFromSpider(
                $article,
                $reference
            );
        }

        throw new ProductNotFoundException(
            sprintf(
                'Impossible de trouver le produit Legrand pour la référence "%s".',
                $reference
            )
        );
    }
}