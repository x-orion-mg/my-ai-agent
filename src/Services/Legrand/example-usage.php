<?php
declare(strict_types=1);

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use  MyAIAgent\Services\Legrand\Product\LegrandProductService;

$http = new LegrandHttpClient();

$urlExtractor = new ProductUrlExtractor(
    $http
);

$imageExtractor = new ProductImageExtractor(
    $http
);

$technicalDataExtractor =
    new ProductTechnicalDataExtractor();

$legrand = new LegrandProductService(
    http: $http,
    urlExtractor: $urlExtractor,
    imageExtractor: $imageExtractor,
    technicalDataExtractor: $technicalDataExtractor,
);

$product = $legrand->getProduct('419160');

if ($product === null) {
    echo 'Produit introuvable';
    exit;
}

echo '<pre>';
print_r($product->toArray());
echo '</pre>';