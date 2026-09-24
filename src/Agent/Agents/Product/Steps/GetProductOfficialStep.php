<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;
use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use  MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use  MyAIAgent\Services\Legrand\Product\LegrandProductService;
use MyAIAgent\Services\Legrand\Support\LegrandUrl;


final class GetProductOfficialStep implements AgentStepInterface
{
    public function id(): string
    {
        return 'get_product_official';
    }

    public function label(): string
    {
        return __('Obtenir les informations du produit officiel', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $input = $context->input();

        $reference = $input['reference'] ?? null;

        if (!$reference) {
            return StepResult::failed(
                __('La référence du produit est manquante.', MY_AI_AGENT_DOMAIN)
            );
        }
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
        $product = $legrand->getProduct($reference);

        $message = sprintf(
            __('Le produit a été trouvé. Voici les informations :
<br> lien du produit sur %s 
<br> l\'image <img src="%s"> 
<br> Caractéristiques : %s', MY_AI_AGENT_DOMAIN),
        $product->url, $product->image, $product->technicalData ) ;

        return StepResult::continue(
            [
                'message' => $message,
                'product' => $product
            ],
        );
    }
}
