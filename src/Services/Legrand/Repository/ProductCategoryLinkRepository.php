<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductCategoryLinkRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_category_links'; }
    public function findByProductId(int $productId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE product_id = %d ORDER BY position ASC", [$productId]); }
    public function attach(int $productId, int $categoryId, int $position = 0): bool { return $this->wpdb->insert(self::tableName(), ['product_id'=>$productId,'category_id'=>$categoryId,'position'=>$position], ['%d','%d','%d']) !== false; }
    public function detach(int $productId, int $categoryId): bool { return $this->wpdb->delete(self::tableName(), ['product_id'=>$productId,'category_id'=>$categoryId], ['%d','%d']) !== false; }
}
