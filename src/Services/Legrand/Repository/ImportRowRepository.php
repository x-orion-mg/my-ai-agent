<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;
final class ImportRowRepository extends AbstractRepository {
    protected static function tableSuffix(): string { return 'import_rows'; }
    public function findByImportAndReference(int $importId, string $reference): ?array {
        return $this->getRow("SELECT * FROM ".self::tableName()." WHERE import_id = %d AND reference = %s LIMIT 1", [$importId,$reference]);
    }
    public function findByImportId(int $importId): array {
        return $this->getResults("SELECT * FROM ".self::tableName()." WHERE import_id = %d ORDER BY id ASC", [$importId]);
    }
}
