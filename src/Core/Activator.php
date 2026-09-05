<?php

declare(strict_types=1);

namespace MyAIAgent\Core;

use MyAIAgent\Repository\ApiKeyRepository;
use MyAIAgent\Repository\ExecutionRepository;
use MyAIAgent\Repository\HistoryRepository;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Settings;

final class Activator
{
    public static function activate(): void
    {
        self::createTables();
        self::ensureStorage();
        self::seedDefaults();

        add_option(
            'my_ai_agent_version',
            MY_AI_AGENT_VERSION
        );

        flush_rewrite_rules();
    }

    public static function createTables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $prompts = PromptRepository::tableName();
        $keys = ApiKeyRepository::tableName();
        $history = HistoryRepository::tableName();
        $executions = ExecutionRepository::tableName();

        $queries = [];

        $queries[] = "CREATE TABLE {$prompts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL DEFAULT '',
            description TEXT NULL,
            content LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) {$charset};";

        $queries[] = "CREATE TABLE {$keys} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(60) NOT NULL DEFAULT '',
            label VARCHAR(191) NOT NULL DEFAULT '',
            api_key TEXT NULL,
            model VARCHAR(191) NOT NULL DEFAULT '',
            priority INT NOT NULL DEFAULT 10,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            error_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY provider (provider),
            KEY is_active (is_active),
            KEY priority (priority)
        ) {$charset};";

        $queries[] = "CREATE TABLE {$history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            provider VARCHAR(60) NOT NULL DEFAULT '',
            prompt_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT '',
            duration FLOAT NOT NULL DEFAULT 0,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY (id),
            KEY status (status),
            KEY product_id (product_id),
            KEY created_at (created_at)
        ) {$charset};";

        $queries[] = "CREATE TABLE {$executions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            agent_id VARCHAR(100) NOT NULL DEFAULT '',
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            current_step INT UNSIGNED NOT NULL DEFAULT 0,
            input LONGTEXT NULL,
            data LONGTEXT NULL,
            error TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            completed_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uuid (uuid),
            KEY agent_id (agent_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};";

        foreach ($queries as $query) {
            dbDelta($query);
        }
    }

    public static function ensureStorage(): void
    {
        $dirs = [
            MY_AI_AGENT_STORAGE_DIR,
            MY_AI_AGENT_STORAGE_DIR . 'logs/',
            MY_AI_AGENT_STORAGE_DIR . 'sessions/',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }

            $htaccess = $dir . '.htaccess';

            if (!file_exists($htaccess)) {
                file_put_contents(
                    $htaccess,
                    "Order deny,allow\nDeny from all\n"
                );
            }

            $index = $dir . 'index.php';

            if (!file_exists($index)) {
                file_put_contents(
                    $index,
                    "<?php\n// Silence is golden.\n"
                );
            }
        }
    }

    private static function seedDefaults(): void
    {
        if (get_option(Settings::OPTION_KEY) === false) {
            add_option(
                Settings::OPTION_KEY,
                Settings::defaults()
            );
        }

        $repository = new PromptRepository();

        if ($repository->count() === 0) {
            $defaults = require MY_AI_AGENT_DIR
                . 'config/default-prompts.php';

            foreach ($defaults as $prompt) {
                $repository->create($prompt);
            }
        }
    }
}
