<?php

declare(strict_types=1);

/**
 * Plugin Name: My AI Agent
 * Description: AI agent platform for WordPress and WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author: X-Orion
 * Text Domain: my-ai-agent
 */

defined('ABSPATH') || exit;

const MY_AI_AGENT_VERSION = '1.0.0';
const MY_AI_AGENT_DOMAIN = 'my-ai-agent';
const MY_AI_AGENT_FILE = __FILE__;
define('MY_AI_AGENT_DIR', plugin_dir_path(__FILE__));
define('MY_AI_AGENT_URL', plugin_dir_url(__FILE__));
const MY_AI_AGENT_STORAGE_DIR = MY_AI_AGENT_DIR . 'storage/';

$autoload = MY_AI_AGENT_DIR . 'vendor/autoload.php';

if (!is_readable($autoload)) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'My AI Agent : les dépendances Composer sont absentes. Exécutez composer install.',
            'my-ai-agent'
        );
        echo '</p></div>';
    });

    return;
}

require_once $autoload;

register_activation_hook(
    MY_AI_AGENT_FILE,
    [MyAIAgent\Core\Activator::class, 'activate']
);

register_deactivation_hook(
    MY_AI_AGENT_FILE,
    [MyAIAgent\Core\Deactivator::class, 'deactivate']
);

add_action('plugins_loaded', static function (): void {
    MyAIAgent\Core\Plugin::instance()->boot();
});
