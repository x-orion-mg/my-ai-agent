<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Provider\ProviderFactory;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Settings;

final class SettingsPage extends AbstractPage
{
    private const NONCE_ACTION = 'my_ai_agent_save_settings';
    private const NONCE_FIELD  = 'my_ai_agent_settings_nonce';

    public function slug(): string
    {
        return 'settings';
    }

    public function title(): string
    {
        return __('Configuration — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Configuration', 'my-ai-agent');
    }

    /**
     * Handle the settings form POST (runs on admin_init).
     */
    public function maybeHandleSubmit(): void
    {
        if (! isset($_POST['my_ai_agent_settings_submit'])) {
            return;
        }

        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer(self::NONCE_ACTION, self::NONCE_FIELD);

        /** @var Settings $settings */
        $settings = $this->container->get(Settings::class);

        $settings->update([
            'default_provider'        => sanitize_key((string) ($_POST['default_provider'] ?? 'openai')),
            'default_prompt_id'       => (int) ($_POST['default_prompt_id'] ?? 0),
            'default_status'          => sanitize_key((string) ($_POST['default_status'] ?? 'draft')),
            'image_max_width'         => max(256, (int) ($_POST['image_max_width'] ?? 1600)),
            'image_quality'           => min(100, max(10, (int) ($_POST['image_quality'] ?? 82))),
            'request_timeout'         => max(10, (int) ($_POST['request_timeout'] ?? 120)),
            'max_error_before_disable'=> max(0, (int) ($_POST['max_error_before_disable'] ?? 5)),
            'log_level'               => sanitize_key((string) ($_POST['log_level'] ?? 'info')),
            'log_retention_days'      => max(1, (int) ($_POST['log_retention_days'] ?? 14)),
            'log_max_files'           => max(1, (int) ($_POST['log_max_files'] ?? 14)),
            'generate_seo'            => isset($_POST['generate_seo']),
            'seo_plugin'              => sanitize_key((string) ($_POST['seo_plugin'] ?? 'auto')),
            'language'                => sanitize_text_field((string) ($_POST['language'] ?? 'fr')),
        ]);

        add_settings_error('my_ai_agent_settings', 'saved', __('Configuration enregistrée.', 'my-ai-agent'), 'updated');
        set_transient('settings_errors', get_settings_errors(), 30);
    }

    public function render(): void
    {
        /** @var Settings $settings */
        $settings = $this->container->get(Settings::class);
        /** @var PromptRepository $prompts */
        $prompts = $this->container->get(PromptRepository::class);
        /** @var ProviderFactory $factory */
        $factory = $this->container->get(ProviderFactory::class);

        $this->view('settings', [
            'settings'    => $settings->all(),
            'providers'   => $factory->available(),
            'prompts'     => $prompts->all(),
            'nonceAction' => self::NONCE_ACTION,
            'nonceField'  => self::NONCE_FIELD,
        ]);
    }
}
