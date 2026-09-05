<?php

declare(strict_types=1);

namespace MyAIAgent\Provider;

use MyAIAgent\Provider\AiProvider\OpenAIProvider;
use MyAIAgent\Provider\AiProvider\OpenRouterProvider;
use RuntimeException;
use MyAIAgent\Logger\Logger;
use MyAIAgent\Services\Settings;

/**
 * Registry/factory of AI providers. New providers can be registered from
 * anywhere (including third-party plugins) via the `aips_register_providers`
 * filter, keeping the core closed for modification but open for extension.
 */
final class ProviderFactory
{
    /** @var array<string, class-string<ProviderInterface>> */
    private array $map;

    private Logger $logger;

    private Settings $settings;

    /** @var array<string, ProviderInterface> */
    private array $instances = [];

    public function __construct(Logger $logger, Settings $settings)
    {
        $this->logger   = $logger;
        $this->settings = $settings;

        $defaults = [
            'openai'     => OpenAIProvider::class,
            'openrouter' => OpenRouterProvider::class,
        ];

        /**
         * Filter the map of available providers.
         *
         * @param array<string, class-string<ProviderInterface>> $defaults
         */
        $this->map = (array) apply_filters('aips_register_providers', $defaults);
    }

    public function make(string $slug): ProviderInterface
    {
        if (isset($this->instances[$slug])) {
            return $this->instances[$slug];
        }

        if (! isset($this->map[$slug])) {
            throw new RuntimeException(
                sprintf(
                    /* translators: %s: provider slug. */
                    __('Fournisseur IA inconnu : « %s ».', 'ai-product-studio'),
                    $slug
                )
            );
        }

        $class    = $this->map[$slug];
        $provider = new $class($this->logger, $this->settings);

        if (! $provider instanceof ProviderInterface) {
            throw new RuntimeException(
                sprintf('La classe %s doit implémenter ProviderInterface.', $class)
            );
        }

        return $this->instances[$slug] = $provider;
    }

    /**
     * @return array<string, string> slug => label
     */
    public function available(): array
    {
        $labels = [];
        foreach (array_keys($this->map) as $slug) {
            $labels[$slug] = $this->make($slug)->label();
        }

        return $labels;
    }
}
