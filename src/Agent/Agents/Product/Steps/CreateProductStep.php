<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product\Steps;

use MyAIAgent\Services\Blog\BlogPostService;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Services\Product\ProductService;
use Throwable;

final class CreateProductStep implements AgentStepInterface
{
    public function __construct(
        private readonly ProductService  $productService,
    ) {
    }

    public function id(): string
    {
        return 'create-product';
    }

    public function label(): string
    {
        return 'Création du produit';
    }

    public function execute(AgentContext $context): StepResult
    {
        $productData = $context->get('data');
        $productInfo = $context->get('product');

        $product = array_merge($productData, [
            'url' => $productInfo['url'] ?? '',
            'image_url' => $productInfo['image'] ?? '',
            'technicalData' => $productInfo['technicalData'] ?? '',
        ]);

        $productName = $product['productName'] ?? null;
        $description = $product['description'] ?? '';

        if (!is_string($productName) || trim($productName) === '') {
            return StepResult::failed(
                'Le nom du produit est manquant.'
            );
        }

        if (!is_string($description) || trim($description) === '') {
            return StepResult::failed(
                'La description du produit est manquante.'
            );
        }

        try {
            $sku = $product['sku'] ?? null;
            $product_id = wc_get_product_id_by_sku($sku);

            if ($product_id) {
               // update product
                $message = __('Le produit a été modifié avec succès.', MY_AI_AGENT_DOMAIN);
            } else {
                $message = __('Le produit a été créé avec succès.', MY_AI_AGENT_DOMAIN);
            }
            $productId = $this->productService->createOrUpdate($product);


            $editUrl = get_edit_post_link($productId);

            $message .= sprintf(
                __(
                    '<a href="%s" target="_blank">Modifier le produit</a>',
                    MY_AI_AGENT_DOMAIN
                ),
                esc_url($editUrl)
            );

            return StepResult::continue([
                'message' => $message,
            ]);

        } catch (Throwable $e) {
            return StepResult::failed(
                $e->getMessage()
            );
        }
    }

}
