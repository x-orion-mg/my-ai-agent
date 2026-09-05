<?php

declare(strict_types=1);

namespace MyAiAgent\Ajax;

/**
 * Base class for AJAX controllers: centralises nonce verification, capability
 * checks and JSON responses. Every handler must call {@see guard()} first.
 */
abstract class AbstractController
{
    protected const string CAPABILITY = 'manage_woocommerce';
    protected const string NONCE      = 'my_ai_agent_nonce';

    /**
     * Verify the request is authenticated and authorised. Terminates with a
     * JSON error on failure.
     */
    protected function guard(): void
    {
        if (! check_ajax_referer(self::NONCE, 'nonce', false)) {
            wp_send_json_error(
                ['message' => __('Jeton de sécurité invalide. Rechargez la page.', 'ai-product-studio')],
                403
            );
        }

        if (! current_user_can(self::CAPABILITY)) {
            wp_send_json_error(
                ['message' => __('Permissions insuffisantes.', 'ai-product-studio')],
                403
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function success(array $data = []): void
    {
        wp_send_json_success($data);
    }

    protected function fail(string $message, int $status = 400, array $extra = []): void
    {
        wp_send_json_error(array_merge(['message' => $message], $extra), $status);
    }

    protected function post(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : $default;
    }

    protected function postInt(string $key, int $default = 0): int
    {
        return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
    }

    protected function postFloat(string $key, float $default = 0.0): float
    {
        return isset($_POST[$key]) ? (float) $_POST[$key] : $default;
    }

    protected function postTextarea(string $key, string $default = ''): string
    {
        return isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$key])) : $default;
    }

    /**
     * @return array<int, int>
     */
    protected function postIntList(string $key): array
    {
        if (! isset($_POST[$key])) {
            return [];
        }

        $raw = wp_unslash($_POST[$key]);

        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)), static fn ($v): bool => $v !== '');
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $raw), static fn (int $v): bool => $v > 0));
    }
}
