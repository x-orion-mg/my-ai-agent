<?php
declare(strict_types=1);

namespace MyAIAgent\Database;
use MyAIAgent\Repository\ApiKeyRepository;
use MyAIAgent\Repository\ExecutionRepository;
use MyAIAgent\Repository\HistoryRepository;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Legrand\Repository\AttributeRepository;
use MyAIAgent\Services\Legrand\Repository\AttributeValueRepository;
use MyAIAgent\Services\Legrand\Repository\BrandRepository;
use MyAIAgent\Services\Legrand\Repository\CategoryRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use MyAIAgent\Services\Legrand\Repository\ProductAttributeValueRepository;
use MyAIAgent\Services\Legrand\Repository\ProductCategoryLinkRepository;
use MyAIAgent\Services\Legrand\Repository\ProductFaqRepository;
use MyAIAgent\Services\Legrand\Repository\ProductImageRepository;
use MyAIAgent\Services\Legrand\Repository\ProductRepository;
use MyAIAgent\Services\Legrand\Repository\ProductSeoRepository;
use MyAIAgent\Services\Legrand\Repository\ProductSourceRepository;
use MyAIAgent\Services\Legrand\Repository\ProductSpecificationRepository;
use MyAIAgent\Services\Legrand\Repository\WooCommerceProductRepository;

final class Schema
{
    public static function prompts(string $charset): string
    {
        return "CREATE TABLE " . PromptRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL DEFAULT '',
            description TEXT NULL,
            content LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) {$charset};";
    }

    public static function  keys(string $charset): string
    {
        $keys = ApiKeyRepository::tableName();
        return "CREATE TABLE {$keys} (
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
    }

    public static function history(string $charset): string
    {
        return "CREATE TABLE " . HistoryRepository::tableName() . " (
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
    }

    public static function executions(string $charset): string
    {
        return "CREATE TABLE " . ExecutionRepository::tableName() . " (
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
    }

    public static function importCsv(string $charset): string
    {
        return "CREATE TABLE " . ImportRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(50) NOT NULL,
            filename VARCHAR(255) NULL,
            status VARCHAR(50) NOT NULL,
            started_at DATETIME NULL,
            finished_at DATETIME NULL,
            total_rows INT UNSIGNED NOT NULL DEFAULT 0,
            inserted INT UNSIGNED NOT NULL DEFAULT 0,
            updated INT UNSIGNED NOT NULL DEFAULT 0,
            unchanged INT UNSIGNED NOT NULL DEFAULT 0,
            errors INT UNSIGNED NOT NULL DEFAULT 0,
            error_message TEXT NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY source (source),
            KEY started_at (started_at)
        ) {$charset};";
    }

    public static function importRow(string $charset): string
    {
        return "CREATE TABLE " . ImportRowRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            import_id BIGINT UNSIGNED NOT NULL,
            reference VARCHAR(100) NOT NULL,
            label VARCHAR(255) NULL,
            family_code VARCHAR(100) NULL,
            family_name VARCHAR(255) NULL,
            ean VARCHAR(20) NULL,
            promotion_price DECIMAL(12,2) NULL,
            price DECIMAL(12,2) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            error TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY reference (reference),
            KEY import_id (import_id),
            KEY ean (ean),
    
            CONSTRAINT fk_import_rows_import
                FOREIGN KEY (import_id)
                REFERENCES " . ImportRepository::tableName() . "(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE
    
        ) {$charset};";
    }

    public static function brand( string $charset ): string
    {
        return "CREATE TABLE " . BrandRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            woocommerce_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY woocommerce_id (woocommerce_id),
            KEY name (name)
        ) {$charset};";
    }

    public static function productSource( string $charset ): string{
        return "CREATE TABLE " . ProductSourceRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            import_row_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            source_type VARCHAR(50) NOT NULL,
            source_url TEXT NULL,
            source_reference VARCHAR(255) NOT NULL,
            raw_json LONGTEXT NULL,
            retrieved_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY import_row_id (import_row_id),
            UNIQUE KEY product_id (product_id),
            KEY source_reference (source_reference),
            KEY source_type (source_type),
            KEY retrieved_at (retrieved_at)
        ) {$charset};";
    }

    public static function Product( string $charset ): string
    {
        return "CREATE TABLE " . ProductRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sku VARCHAR(100) NOT NULL,
            ean VARCHAR(100) NULL,
            brand_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            short_description TEXT NULL,
            description LONGTEXT NULL,
            slug VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            data_hash VARCHAR(255) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            PRIMARY KEY (id),
            UNIQUE KEY sku (sku),
            UNIQUE KEY slug (slug),
            KEY ean (ean),
            KEY brand_id (brand_id),
            KEY status (status)
        ) {$charset};";
    }

    public static function productImage( string $charset ): string
    {
        return "CREATE TABLE " . ProductImageRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            image_url TEXT NOT NULL,
            alt VARCHAR(255) NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            is_main TINYINT(1) NOT NULL DEFAULT 0,
            type VARCHAR(50) NULL,
            hash VARCHAR(255) NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY product_position (product_id, position),
            KEY product_main (product_id, is_main)
        ) {$charset};";
    }

    public static function productFaq( string $charset ): string
    {
        return "CREATE TABLE " . ProductFaqRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            question TEXT NOT NULL,
            answer LONGTEXT NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY product_position (product_id, position)
        ) {$charset};";
    }

    public static function productSpecification( string $charset ): string
    {
        return "CREATE TABLE " . ProductSpecificationRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            value LONGTEXT NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY product_position (product_id, position)
        ) {$charset};";
    }

    public static function category( string $charset ): string
    {
        return "CREATE TABLE " . CategoryRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            parent_id BIGINT UNSIGNED NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            woocommerce_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY woocommerce_id (woocommerce_id),
            KEY parent_id (parent_id),
            KEY name (name)
        ) {$charset};";
    }

    public static function productCategoryLink( string $charset ): string
    {
        return "CREATE TABLE " . ProductCategoryLinkRepository::tableName() . " (
            product_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (product_id, category_id),
            KEY category_id (category_id)
        ) {$charset};";
    }

    public static function attribute( string $charset ): string
    {
        return "CREATE TABLE " . AttributeRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            woocommerce_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            UNIQUE KEY woocommerce_id (woocommerce_id)
        ) {$charset};";
    }

    public static function attributeValue( string $charset ): string
    {
        return "CREATE TABLE " . AttributeValueRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            attribute_id BIGINT UNSIGNED NOT NULL,
            value VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY attribute_slug (attribute_id, slug),
            KEY attribute_id (attribute_id)
        ) {$charset};";
    }

    public static function productAttributeValue( string $charset ): string
    {
        return "CREATE TABLE " . ProductAttributeValueRepository::tableName() . " (
            product_id BIGINT UNSIGNED NOT NULL,
            attribute_id BIGINT UNSIGNED NOT NULL,
            attribute_value_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (product_id, attribute_id, attribute_value_id),
            KEY attribute_id (attribute_id),
            KEY attribute_value_id (attribute_value_id)
        ) {$charset};";
    }
    public static function productSeo( string $charset ): string
    {
        return "CREATE TABLE " . ProductSeoRepository::tableName() . " (
            product_id BIGINT UNSIGNED NOT NULL,
            meta_title VARCHAR(255) NULL,
            meta_description TEXT NULL,
            slug VARCHAR(255) NULL,
            focus_keyword VARCHAR(255) NULL,
            canonical_url TEXT NULL,
            robots VARCHAR(50) NULL,
            PRIMARY KEY (product_id),
            KEY slug (slug)
        ) {$charset};";
    }
    public static function wooCommerceProduct( string $charset ): string
    {
        return "CREATE TABLE " . WooCommerceProductRepository::tableName() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            woocommerce_id BIGINT UNSIGNED NULL,
            sku VARCHAR(100) NULL,
            status VARCHAR(50) NULL,
            last_sync DATETIME NULL,
            last_sync_hash VARCHAR(255) NULL,
            last_error TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY product_id (product_id),
            UNIQUE KEY woocommerce_id (woocommerce_id),
            KEY sku (sku),
            KEY status (status)
        ) {$charset};";
    }

}
