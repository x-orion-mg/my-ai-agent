<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductTagLinkRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_tag_links'; }
    public function findByProductId(int $productId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE product_id = %d", [$productId]); }
    public function attach(int $productId, int $tagId): bool { return $this->wpdb->insert(self::tableName(), ['product_id'=>$productId,'tag_id'=>$tagId], ['%d','%d']) !== false; }
    public function detach(int $productId, int $tagId): bool { return $this->wpdb->delete(self::tableName(), ['product_id'=>$productId,'tag_id'=>$tagId], ['%d','%d']) !== false; }
}
