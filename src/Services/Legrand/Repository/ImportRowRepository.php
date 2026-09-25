<?php

declare(strict_types=1);
namespace MyAIAgent\Services\Legrand\Repository;

use MyAIAgent\Services\Legrand\ImportRow\ImportRowSaveResult;

final class ImportRowRepository extends AbstractRepository
{
    protected static function tableSuffix(): string
    {
        return 'import_rows';
    }

    /**
     * Crée ou met à jour une ligne en fonction de la référence.
     *
     * @param array<string, mixed> $data
     */
    public function save(
        int $importId,
        array $data
    ): ImportRowSaveResult {
        $reference = trim(
            (string) ($data['reference'] ?? '')
        );

        if ($reference === '') {
            return new ImportRowSaveResult(
                id :0,
                inserted : false,
                updated : false
            );
        }

        $existing = $this->findByReference(
            $reference
        );

        if ($existing !== null) {
            $updated = $this->update(
                (int) $existing['id'],
                $importId,
                $data
            );

            return new ImportRowSaveResult(
                id :$updated
                    ? (int) $existing['id']
                    : 0,
                inserted : false,
                updated : true
            );
        }
        $reId = $this->insertRow($importId, $data);
        $reId = $this->insertRow($importId, $data);
        return new ImportRowSaveResult(
            id :$reId,
            inserted : true,
            updated : false
        );

    }

    /**
     * Recherche une ligne par référence.
     */
    public function findByReference(
        string $reference
    ): ?array {
        return $this->getRow(
            'SELECT *
             FROM ' . self::tableName() . '
             WHERE reference = %s
             LIMIT 1',
            [
                $reference,
            ]
        );
    }

    /**
     * Insère une nouvelle ligne.
     *
     * @param array<string, mixed> $data
     */
    private function insertRow(int $importId, array $data): int
    {
        $now = current_time('mysql');

        $result = $this->wpdb->insert(
            self::tableName(),
            [
                'import_id' => $importId,

                'reference' => $data['reference'] ?? null,
                'label' => $data['label'] ?? null,

                'family_code' => $data['family_code'] ?? null,
                'family_name' => $data['family_name'] ?? null,

                'ean' => $data['ean'] ?? null,

                'promotion_price' =>
                    $data['promotion_price'] ?? null,

                'price' =>
                    $data['price'] ?? null,

                'status' =>
                    $data['status'] ?? 'pending',

                'error' =>
                    $data['error'] ?? null,

                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            return 0;
        }

        return (int) $this->wpdb->insert_id;
    }


    /**
     * Met à jour une ligne existante.
     *
     * @param array<string, mixed> $data
     */
    private function update(
        int $id,
        int $importId,
        array $data
    ): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    /*
                     * La référence existe déjà.
                     * On rattache donc la ligne au nouvel import.
                     */
                    'import_id' => $importId,

                    'reference' => $data['reference'] ?? null,
                    'label' => $data['label'] ?? null,

                    'family_code' => $data['family_code'] ?? null,
                    'family_name' => $data['family_name'] ?? null,

                    'ean' => $data['ean'] ?? null,

                    'promotion_price' =>
                        $data['promotion_price'] ?? null,

                    'price' =>
                        $data['price'] ?? null,

                    'status' =>
                        $data['status'] ?? 'pending',

                    'error' =>
                        $data['error'] ?? null,

                    'updated_at' =>
                        current_time('mysql'),
                ],
                [
                    'id' => $id,
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%f',
                    '%f',
                    '%s',
                    '%s',
                    '%s',
                ],
                [
                    '%d',
                ]
            ) !== false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByImportId(
        int $importId
    ): array {
        return $this->getResults(
            'SELECT *
             FROM ' . self::tableName() . '
             WHERE import_id = %d
             ORDER BY id ASC',
            [
                $importId,
            ]
        );
    }
}
