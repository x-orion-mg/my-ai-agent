<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Repository;

use MyAIAgent\Services\Legrand\ImportRow\ImportRowSaveResult;

final class ImportRowRepository extends AbstractRepository
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_PROCESSING = 'processing';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_FAILED = 'failed';
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
                id: 0,
                inserted: false,
                updated: false
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
                id: $updated
                    ? (int) $existing['id']
                    : 0,
                inserted: false,
                updated: $updated
            );
        }

        $id = $this->insertRow(
            $importId,
            $data
        );

        return new ImportRowSaveResult(
            id: $id,
            inserted: $id > 0,
            updated: false
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
     * Sauvegarde les données récupérées par SourceJobHandler.
     *
     * @param array<string,mixed> $source
     */
    public function saveSourceData(
        int $id,
        array $source
    ): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'source_url' => $source['url'] ?? null,

                    'source_image' => $source['image'] ?? null,

                    'source_technical_data' =>
                        $source['technicalData'] ?? null,

                    'source_retrieved_at' =>
                        current_time('mysql'),

                    'updated_at' =>
                        current_time('mysql'),
                ],
                [
                    'id' => $id,
                ],
                [
                    '%s',
                    '%s',
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
     * Retourne les données SourceJobHandler
     * déjà présentes en base.
     *
     * @return array<string,mixed>|null
     */
    public function getCachedSource(
        int $id
    ): ?array {
        $row = $this->find($id);

        if ($row === null) {
            return null;
        }

        /*
         * Pas encore récupéré.
         */
        if (
            empty($row['source_retrieved_at'])
        ) {
            return null;
        }

        return [
            'reference' =>
                (string) $row['reference'],

            'url' =>
                $this->nullableString(
                    $row['source_url'] ?? null
                ),

            'image' =>
                $this->nullableString(
                    $row['source_image'] ?? null
                ),

            'technicalData' =>
                $this->nullableString(
                    $row['source_technical_data'] ?? null
                ),
        ];
    }

    /**
     * Insère une nouvelle ligne.
     *
     * @param array<string,mixed> $data
     */
    private function insertRow(
        int $importId,
        array $data
    ): int {
        $now = current_time('mysql');

        $result = $this->wpdb->insert(
            self::tableName(),
            [
                'import_id' => $importId,

                'reference' =>
                    $data['reference'] ?? null,

                'label' =>
                    $data['label'] ?? null,

                'family_code' =>
                    $data['family_code'] ?? null,

                'family_name' =>
                    $data['family_name'] ?? null,

                'ean' =>
                    $data['ean'] ?? null,

                'type' =>
                    $data['type'] ?? null,

                'fonction' =>
                    $data['fonction'] ?? null,

                'finition' =>
                    $data['finition'] ?? null,

                'gamme' =>
                    $data['gamme'] ?? null,

                'famille' =>
                    $data['famille'] ?? null,

                'sous_famille' =>
                    $data['sous_famille'] ?? null,

                'stock' =>
                    $data['stock'] ?? null,

                'product_name' =>
                    $data['product_name'] ?? null,

                'short_description' =>
                    $data['short_description'] ?? null,

                'description' =>
                    $data['description'] ?? null,

                'category' =>
                    $data['category'] ?? null,

                'alt' =>
                    $data['alt'] ?? null,

                'meta_description' =>
                    $data['meta_description'] ?? null,

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
                '%d', // import_id

                '%s', // reference
                '%s', // label
                '%s', // family_code
                '%s', // family_name
                '%s', // ean

                '%s', // type
                '%s', // fonction
                '%s', // finition
                '%s', // gamme
                '%s', // famille
                '%s', // sous_famille
                '%d', // stock

                '%s', // product_name
                '%s', // short_description
                '%s', // description
                '%s', // category
                '%s', // alt
                '%s', // meta_description

                '%f', // promotion_price
                '%f', // price

                '%s', // status
                '%s', // error

                '%s', // created_at
                '%s', // updated_at
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
     * @param array<string,mixed> $data
     */
    private function update(
        int $id,
        int $importId,
        array $data
    ): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'import_id' => $importId,

                    'reference' =>
                        $data['reference'] ?? null,

                    'label' =>
                        $data['label'] ?? null,

                    'family_code' =>
                        $data['family_code'] ?? null,

                    'family_name' =>
                        $data['family_name'] ?? null,

                    'ean' =>
                        $data['ean'] ?? null,

                    'type' =>
                        $data['type'] ?? null,

                    'fonction' =>
                        $data['fonction'] ?? null,

                    'finition' =>
                        $data['finition'] ?? null,

                    'gamme' =>
                        $data['gamme'] ?? null,

                    'famille' =>
                        $data['famille'] ?? null,

                    'sous_famille' =>
                        $data['sous_famille'] ?? null,

                    'stock' =>
                        $data['stock'] ?? null,

                    'product_name' =>
                        $data['product_name'] ?? null,

                    'short_description' =>
                        $data['short_description'] ?? null,

                    'description' =>
                        $data['description'] ?? null,

                    'category' =>
                        $data['category'] ?? null,

                    'alt' =>
                        $data['alt'] ?? null,

                    'meta_description' =>
                        $data['meta_description'] ?? null,

                    /*
                     * IMPORTANT :
                     * On ne touche PAS aux données source ici.
                     *
                     * Les données SourceJobHandler sont gérées
                     * exclusivement par saveSourceData().
                     */

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
                    '%d', // import_id
                    '%s', // reference
                    '%s', // label
                    '%s', // family_code
                    '%s', // family_name
                    '%s', // ean

                    '%s', // type
                    '%s', // fonction
                    '%s', // finition
                    '%s', // gamme
                    '%s', // famille
                    '%s', // sous_famille
                    '%d', // stock

                    '%s', // product_name
                    '%s', // short_description
                    '%s', // description
                    '%s', // category
                    '%s', // alt
                    '%s', // meta_description

                    '%f', // promotion_price
                    '%f', // price
                    '%s', // status
                    '%s', // error
                    '%s', // updated_at
                ],
                [
                    '%d', // id
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

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    public function updateStatus(
        string  $reference,
        string $status
    ): bool {
        return $this->wpdb->update(
                self::tableName(),
                [
                    'status' => $status,
                    'updated_at' => current_time('mysql'),
                ],
                [
                    'reference' => $reference,
                ],
                [
                    '%s',
                    '%s',
                ],
                [
                    '%s',
                ]
            ) !== false;
    }
}