<?php

declare(strict_types=1);

namespace MyAIAgent\Provider;

use RuntimeException;
use MyAIAgent\Logger\Logger;
use MyAIAgent\Services\Settings;

/**
 * Shared behaviour for HTTP-based providers: request execution, timeout,
 * logging and error normalisation. Concrete providers only build the request
 * body/headers and parse the response.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected Logger $logger;

    protected Settings $settings;

    public function __construct(Logger $logger, Settings $settings)
    {
        $this->logger   = $logger;
        $this->settings = $settings;
    }

    public function requiresApiKey(): bool
    {
        return true;
    }

    /**
     * Perform a JSON HTTP POST request and decode the response.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed>  $body
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    protected function post(string $url, array $headers, array $body): array
    {
        $timeout = (int) $this->settings->get('request_timeout', 120);

        $this->logger->debug('Requête IA envoyée.', [
            'provider' => $this->slug(),
            'url'      => $url,
        ]);

        $response = wp_remote_post($url, [
            'timeout' => $timeout,
            'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
            'body'    => (string) wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            throw new RuntimeException(
                sprintf('%s: %s', $this->slug(), $response->get_error_message())
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw  = (string) wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            $this->logger->error('Réponse IA en erreur.', [
                'provider' => $this->slug(),
                'code'     => $code,
                'body'     => mb_substr($raw, 0, 2000),
            ]);

            throw new RuntimeException(
                sprintf(
                    /* translators: 1: provider, 2: HTTP code, 3: message. */
                    __('%1$s a renvoyé une erreur HTTP %2$d : %3$s', 'ai-product-studio'),
                    $this->slug(),
                    $code,
                    mb_substr($raw, 0, 500)
                )
            );
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw new RuntimeException(
                sprintf('%s: réponse JSON illisible.', $this->slug())
            );
        }

        return $decoded;
    }

    /**
     * Build a data URI from a base64 image descriptor.
     *
     * @param array{mime: string, data: string} $image
     */
    protected function dataUri(array $image): string
    {
        return sprintf('data:%s;base64,%s', $image['mime'], $image['data']);
    }
}
