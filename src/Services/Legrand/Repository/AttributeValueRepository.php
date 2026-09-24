<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class AttributeValueRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'attribute_values'; }
    public function findByAttributeAndSlug(int $attributeId, string $slug): ?array { return $this->getRow("SELECT * FROM ".self::tableName()." WHERE attribute_id = %d AND slug = %s LIMIT 1", [$attributeId,$slug]); }
    public function findByAttributeId(int $attributeId): array { return $this->getResults("SELECT * FROM ".self::tableName()." WHERE attribute_id = %d ORDER BY value ASC", [$attributeId]); }
}
