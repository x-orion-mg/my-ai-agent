<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductSeoRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_seo'; }
    public function findByProductId(int $productId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE product_id = %d LIMIT 1", [$productId]); }
    public function saveForProduct(int $productId, array $data): bool {
        $existing = $this->findByProductId($productId);
        $data['product_id'] = $productId;
        if ($existing) return $this->wpdb->update(self::tableName(), $data, ['product_id'=>$productId], null, ['%d']) !== false;
        return $this->wpdb->insert(self::tableName(), $data) !== false;
    }
}
