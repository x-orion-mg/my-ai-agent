<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Provider\ProviderFactory;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Settings;

final class GenerateProductPage extends AbstractPage
{
    public function slug(): string
    {
        return 'generate-product';
    }

    public function title(): string
    {
        return __('Générer un produit — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Générer produit', 'my-ai-agent');
    }

    public function render(): void
    {
        /** @var PromptRepository $prompts */
        $prompts = $this->container->get(PromptRepository::class);
        /** @var ProviderFactory $factory */
        $factory = $this->container->get(ProviderFactory::class);
        /** @var Settings $settings */
        $settings = $this->container->get(Settings::class);

        $this->view('generate-product', [
            'title' => $this->title(),
            'slug' => $this->slug(),
            'prompts'        => $prompts->all(true),
            'providers'      => $factory->available(),
            'defaultProvider'=> (string) $settings->get('default_provider', 'openrouter'),
            'wooActive'      => class_exists('WooCommerce'),
        ]);
    }
}
