<?php

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use  MyAIAgent\Services\Legrand\Product\LegrandProductService;
use MyAIAgent\Services\Legrand\Support\LegrandUrl;

$http = new LegrandHttpClient();

$urlExtractor = new ProductUrlExtractor(
    $http
);

$imageExtractor = new ProductImageExtractor();

$technicalDataExtractor = new ProductTechnicalDataExtractor();

$legrandUrl = new LegrandUrl($http);

$legrand = new LegrandProductService(
    http: $http,
    urlExtractor: $urlExtractor,
    imageExtractor: $imageExtractor,
    technicalDataExtractor: $technicalDataExtractor,
    legrandUrl: $legrandUrl
);

$product = $legrand->getProduct('419160');

if ($product === null) {
    echo 'Produit introuvable';
    exit;
}

echo '<pre>';
print_r($product->toArray());
echo '</pre>';