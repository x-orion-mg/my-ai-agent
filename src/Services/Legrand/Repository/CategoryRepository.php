<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class CategoryRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'categories'; }
    public function findBySlug(string $slug): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE slug = %s LIMIT 1", [$slug]); }
    public function findChildren(int $parentId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE parent_id = %d ORDER BY name ASC", [$parentId]); }
    public function findRoots(): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE parent_id IS NULL ORDER BY name ASC"); }
}
