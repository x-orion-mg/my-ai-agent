<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class WooCommerceProductRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'woocommerce_products'; }
    public function findByProductId(int $productId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE product_id = %d LIMIT 1", [$productId]); }
    public function findByWooCommerceId(int $woocommerceId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE woocommerce_id = %d LIMIT 1", [$woocommerceId]); }
}
