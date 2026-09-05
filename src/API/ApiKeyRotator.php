<?php

declare(strict_types=1);

namespace MyAIAgent\API;

use MyAIAgent\API\ApiKey;
use MyAIAgent\Repository\ApiKeyRepository;
use RuntimeException;
use MyAIAgent\Services\Settings;

/**
 * Provides the ordered list of usable API keys for a provider and records the
 * outcome (success/failure) so the pool self-heals over time.
 *
 * Rotation strategy: keys are tried by ascending priority, then by fewest
 * accumulated errors, then by least-recently used. A key that reaches the
 * configured error threshold is automatically deactivated.
 */
final class ApiKeyRotator
{
    private ApiKeyRepository $repository;

    private Settings $settings;

    public function __construct(ApiKeyRepository $repository, Settings $settings)
    {
        $this->repository = $repository;
        $this->settings   = $settings;
    }

    /**
     * Return the ordered candidate keys for a provider.
     *
     * @return array<int, ApiKey>
     *
     * @throws RuntimeException when no active key exists.
     */
    public function candidates(string $provider): array
    {
        $keys = $this->repository->activeForProvider($provider);

        if ($keys === []) {
            throw new RuntimeException(
                sprintf(
                    /* translators: %s: provider slug. */
                    __('Aucune clé API active pour le fournisseur « %s ». Ajoutez-en une dans l\'onglet API.', 'my-ai-agent'),
                    $provider
                )
            );
        }

        return $keys;
    }

    public function reportSuccess(ApiKey $key): void
    {
        $this->repository->markUsed($key->id);
        $this->repository->resetErrors($key->id);
    }

    public function reportFailure(ApiKey $key): void
    {
        $threshold = (int) $this->settings->get('max_error_before_disable', 5);
        $this->repository->markUsed($key->id);
        $this->repository->incrementError($key->id, $threshold);
    }
}
