<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class AttributeRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'attributes'; }
    public function findBySlug(string $slug): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE slug = %s LIMIT 1", [$slug]); }
}
