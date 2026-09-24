<?php

declare(strict_types=1);

namespace MyAIAgent\Core;

use MyAIAgent\Database\Database;
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
        Database::createTables();
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
