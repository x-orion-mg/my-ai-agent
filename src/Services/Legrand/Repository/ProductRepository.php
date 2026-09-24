<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'products'; }
    public function findBySku(string $sku): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE sku = %s LIMIT 1", [$sku]); }
    public function findBySlug(string $slug): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE slug = %s LIMIT 1", [$slug]); }
    public function findByEan(string $ean): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE ean = %s LIMIT 1", [$ean]); }
}
