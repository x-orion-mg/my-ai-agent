<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductImageRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_images'; }
    public function findByProductId(int $productId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE product_id = %d ORDER BY position ASC, id ASC", [$productId]); }
    public function findMain(int $productId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE product_id = %d AND is_main = 1 ORDER BY position ASC, id ASC LIMIT 1", [$productId]); }
}
