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
        $product = $context->get('data');

        if (!is_array($product)) {
            return StepResult::failed(
                'Les données du produit sont manquantes ou invalides.'
            );
        }

        $productName = $product['productName'] ?? null;
        $shortDescription = $product['shortDescription'] ?? '';
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

        if (!is_string($shortDescription)) {
            $shortDescription = '';
        }

        try {
            $productId = $this->productService->create($product);

            $editUrl = get_edit_post_link($productId);

            $message = sprintf(
                __(
                    'Le produit a été créé avec succès. <a href="%s" target="_blank">Modifier le produit</a>',
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
