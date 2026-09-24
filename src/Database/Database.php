<?php
declare(strict_types=1);

namespace MyAIAgent\Database;
final class Database
{
    public static function createTables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        foreach (self::tables($charset) as $sql) {
            dbDelta($sql);
        }
    }

    private static function tables(string $charset): array
    {
        return [
            Schema::prompts($charset),
            Schema::keys($charset),
            Schema::history($charset),
            Schema::executions($charset),
            Schema::importCsv($charset),
            Schema::importRow($charset),
            Schema::brand($charset),
            Schema::productSource($charset),
            Schema::Product($charset),
            Schema::productImage($charset),
            Schema::productFaq($charset),
            Schema::productSpecification($charset),
            Schema::category($charset),
            Schema::productCategoryLink($charset),
            Schema::attribute($charset),
            Schema::attributeValue($charset),
            Schema::productAttributeValue($charset),
            Schema::productSeo($charset),
            Schema::wooCommerceProduct($charset),
        ];
    }
}
