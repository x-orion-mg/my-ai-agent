<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductSourceRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_sources'; }
    public function findByImportRowId(int $importRowId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE import_row_id = %d LIMIT 1", [$importRowId]); }
    public function findByProductId(int $productId): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE product_id = %d LIMIT 1", [$productId]); }
    public function findBySourceReference(string $reference): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE source_reference = %s LIMIT 1", [$reference]); }
}
