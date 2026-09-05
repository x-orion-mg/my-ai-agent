<?php

declare(strict_types=1);

namespace MyAIAgent\AI;

use MyAIAgent\API\ApiKeyRotator;
use MyAIAgent\Provider\ProviderFactory;
use MyAILib\MyAI;
use MyAILib\Response\AIResponse;
use Throwable;

final class AIService
{
    public function __construct(
        private readonly ProviderFactory $providerFactory,
        private readonly ApiKeyRotator $keyRotator,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     * @throws Throwable
     */
    public function ask(
        string $providerSlug,
        string $prompt,
        array $options = []
    ): AIResult {
        $provider = $this->providerFactory->make(
            $providerSlug
        );

        if (! $provider->requiresApiKey()) {
            return $this->askWithoutApiKey(
                $providerSlug,
                $prompt,
                $options
            );
        }

        $keys = $this->keyRotator->candidates(
            $providerSlug
        );

        $lastException = null;

        foreach ($keys as $key) {
            try {
                $config = [
                    'provider' => $providerSlug,
                    'api_key' => $key->apiKey,
                    'model' => $key->model,
                ];

                $ai = MyAI::create($config);

                if (isset($options['session_id'])) {
                    $ai->session(
                        (string) $options['session_id']
                    );
                }

                if (isset($options['system_prompt'])) {
                    $ai->setSystemPrompt(
                        (string) $options['system_prompt']
                    );
                }

                $response = $ai->ask($prompt);

                $this->keyRotator->reportSuccess($key);

                return $this->mapResponse($response);
            } catch (Throwable $exception) {
                $lastException = $exception;

                $this->keyRotator->reportFailure($key);
            }
        }

        throw $lastException
            ?? new \RuntimeException(
                sprintf(
                    'Toutes les clés API du fournisseur "%s" ont échoué.',
                    $providerSlug
                )
            );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function askWithoutApiKey(
        string $providerSlug,
        string $prompt,
        array $options
    ): AIResult {
        $config = [
            'provider' => $providerSlug,
        ];

        if (isset($options['model'])) {
            $config['model'] = $options['model'];
        }

        $ai = MyAI::create($config);

        if (isset($options['session_id'])) {
            $ai->session(
                (string) $options['session_id']
            );
        }

        if (isset($options['system_prompt'])) {
            $ai->setSystemPrompt(
                (string) $options['system_prompt']
            );
        }

        return $this->mapResponse(
            $ai->ask($prompt)
        );
    }

    /**
     * Convert MyAILib response to our internal result.
     */
    private function mapResponse(AIResponse $response): AIResult
    {
        return new AIResult(
            text: $response->text(),
            provider: $response->provider(),
            model: $response->model(),
            finishReason: $response->finishReason(),
            usage: $response->usage(),
            metadata: $response->metadata(),
        );
    }
}
