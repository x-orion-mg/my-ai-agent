<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ProductAttributeValueRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'product_attribute_values'; }
    public function findByProductId(int $productId): array {
        return $this->getResults(
            "SELECT pav.*, a.name AS attribute_name, av.value AS attribute_value
             FROM ".self::tableName()." pav
             INNER JOIN ".AttributeRepository::tableName()." a ON a.id = pav.attribute_id
             INNER JOIN ".AttributeValueRepository::tableName()." av ON av.id = pav.attribute_value_id
             WHERE pav.product_id = %d ORDER BY pav.position ASC",
            [$productId]
        );
    }
    public function attach(int $productId, int $attributeId, int $attributeValueId, int $position = 0): bool {
        return $this->wpdb->insert(self::tableName(), ['product_id'=>$productId,'attribute_id'=>$attributeId,'attribute_value_id'=>$attributeValueId,'position'=>$position], ['%d','%d','%d','%d']) !== false;
    }
    public function detach(int $productId, int $attributeId, int $attributeValueId): bool {
        return $this->wpdb->delete(self::tableName(), ['product_id'=>$productId,'attribute_id'=>$attributeId,'attribute_value_id'=>$attributeValueId], ['%d','%d','%d']) !== false;
    }
}
