<?php

declare(strict_types=1);

namespace MyAIAgent\Database;

final class Migration
{
    public static function run(): void
    {
        $currentVersion = (string) get_option(
            'my_ai_agent_db_version',
            '1.0.0'
        );

        $migrations = [
            '1.1.0' => self::migrateTo110(...),
            '1.2.0' => self::migrateTo120(...),
        ];

        foreach ($migrations as $version => $migration) {
            if (version_compare($currentVersion, $version, '>=')) {
                continue;
            }

            $migration();

            update_option(
                'my_ai_agent_db_version',
                $version
            );

            $currentVersion = $version;
        }
    }

    private static function migrateTo110(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'my_ai_agent_legrand_import_rows';

        self::addColumn(
            $table,
            'product_name',
            'VARCHAR(255) NULL AFTER ean'
        );

        self::addColumn(
            $table,
            'short_description',
            'TEXT NULL AFTER product_name'
        );

        self::addColumn(
            $table,
            'description',
            'LONGTEXT NULL AFTER short_description'
        );

        self::addColumn(
            $table,
            'category',
            'TEXT NULL AFTER description'
        );

        self::addColumn(
            $table,
            'alt',
            'VARCHAR(255) NULL AFTER category'
        );

        self::addColumn(
            $table,
            'meta_description',
            'TEXT NULL AFTER alt'
        );

        self::addColumn(
            $table,
            'source_url',
            'TEXT NULL AFTER meta_description'
        );

        self::addColumn(
            $table,
            'source_image',
            'TEXT NULL AFTER source_url'
        );

        self::addColumn(
            $table,
            'source_technical_data',
            'LONGTEXT NULL AFTER source_image'
        );

        self::addColumn(
            $table,
            'source_retrieved_at',
            'DATETIME NULL AFTER source_technical_data'
        );
    }

    private static function migrateTo120(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'my_ai_agent_legrand_import_rows';

        self::addColumn(
            $table,
            'type',
            'VARCHAR(255) NULL AFTER ean'
        );

        self::addColumn(
            $table,
            'fonction',
            'VARCHAR(255) NULL AFTER type'
        );

        self::addColumn(
            $table,
            'finition',
            'VARCHAR(255) NULL AFTER fonction'
        );

        self::addColumn(
            $table,
            'gamme',
            'VARCHAR(255) NULL AFTER finition'
        );

        self::addColumn(
            $table,
            'famille',
            'VARCHAR(255) NULL AFTER gamme'
        );

        self::addColumn(
            $table,
            'sous_famille',
            'VARCHAR(255) NULL AFTER famille'
        );

        self::addColumn(
            $table,
            'stock',
            'INT NULL AFTER sous_famille'
        );
    }

    private static function addColumn(
        string $table,
        string $column,
        string $definition
    ): void {
        global $wpdb;

        if (self::columnExists($table, $column)) {
            return;
        }

        $wpdb->query(
            "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}"
        );
    }

    private static function columnExists(
        string $table,
        string $column
    ): bool {
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