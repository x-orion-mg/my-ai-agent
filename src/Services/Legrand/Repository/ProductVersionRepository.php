<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductVersionRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_versions'; }
    public function findByProductId(int $productId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE product_id = %d ORDER BY version DESC", [$productId]); }
    public function findVersion(int $productId, int $version): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE product_id = %d AND version = %d LIMIT 1", [$productId,$version]); }
}
