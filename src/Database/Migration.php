<?php


declare(strict_types=1);

namespace MyAIAgent\Database;

final class Migration
{
    private const string VERSION = '1.1.0';

    public static function run(): void
    {
        $currentVersion = (string)get_option(
            'my_ai_agent_db_version',
            '1.0.0'
        );

        if (version_compare(
            $currentVersion,
            self::VERSION,
            '>='
        )) {
            return;
        }

        self::migrateTo110();

        update_option(
            'my_ai_agent_db_version',
            self::VERSION
        );
    }

    private static function migrateTo110(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'my_ai_agent_legrand_import_rows';

        $columns = [
            'product_name' => "
                ADD COLUMN product_name VARCHAR(255) NULL
                AFTER ean
            ",

            'short_description' => "
                ADD COLUMN short_description TEXT NULL
                AFTER product_name
            ",

            'description' => "
                ADD COLUMN description LONGTEXT NULL
                AFTER short_description
            ",

            'category' => "
                ADD COLUMN category TEXT NULL
                AFTER description
            ",

            'alt' => "
                ADD COLUMN alt VARCHAR(255) NULL
                AFTER category
            ",

            'meta_description' => "
                ADD COLUMN meta_description TEXT NULL
                AFTER alt
            ",

            'source_url' => "
                ADD COLUMN source_url TEXT NULL
                AFTER meta_description
            ",

            'source_image' => "
                ADD COLUMN source_image TEXT NULL
                AFTER source_url
            ",

            'source_technical_data' => "
                ADD COLUMN source_technical_data LONGTEXT NULL
                AFTER source_image
            ",

            'source_retrieved_at' => "
                ADD COLUMN source_retrieved_at DATETIME NULL
                AFTER source_technical_data
            ",
        ];

        foreach ($columns as $column => $sql) {
            if (self::columnExists($table, $column)) {
                continue;
            }

            $wpdb->query(
                "ALTER TABLE {$table} {$sql}"
            );
        }
    }

    private static function columnExists(
        string $table,
        string $column
    ): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW COLUMNS FROM {$table} LIKE %s",
                $column
            )
        );

        return $result !== null;
    }
}