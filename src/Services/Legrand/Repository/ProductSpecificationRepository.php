<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductSpecificationRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_specifications'; }
    public function findByProductId(int $productId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE product_id = %d ORDER BY position ASC, id ASC", [$productId]); }
}
