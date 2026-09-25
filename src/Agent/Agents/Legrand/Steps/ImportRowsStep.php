<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand\Steps;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Services\File\FileStorageService;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use Throwable;

final readonly class ImportRowsStep implements AgentStepInterface
{
    public function __construct(
        private  ImportRepository $importRepository,
        private  ImportRowRepository $importRowRepository,
        private  FileStorageService $fileStorageService,
    ) {
    }

    public function id(): string
    {
        return 'import_rows';
    }

    public function label(): string
    {
        return __('Importation des lignes CSV', MY_AI_AGENT_DOMAIN);
    }

    public function execute(
        AgentContext $context
    ): StepResult {
        /*
         * Récupération de l'ID de l'import.
         */
        $importId = (int) $context->get('import_id');

        if ($importId <= 0) {
            return StepResult::failed(
                __('ID de l\'import invalide.', MY_AI_AGENT_DOMAIN)
            );
        }

        $import = $this->importRepository->find(
            $importId
        );

        if ($import === null) {
            return StepResult::failed(
                'Import introuvable.'
            );
        }

        $filename = (string) (
            $import['filename'] ?? ''
        );

        if ($filename === '') {
            return StepResult::failed(
                'Nom du fichier CSV introuvable.'
            );
        }

        /*
         * Récupération du chemin physique du fichier.
         */
        try {
            $filePath =
                $this->fileStorageService->getPath(
                    'legrand',
                    $filename
                );
        } catch (Throwable $exception) {
            return StepResult::failed(
                $exception->getMessage()
            );
        }

        if (!is_file($filePath)) {
            return StepResult::failed(
                __('Le fichier CSV est introuvable.', MY_AI_AGENT_DOMAIN)
            );
        }

        /*
         * Ouverture du CSV.
         */
        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return StepResult::failed(
                __('Impossible d\'ouvrir le fichier CSV.', MY_AI_AGENT_DOMAIN)
            );
        }

        $inserted = 0;
        $updated = 0;
        $unchanged = 0;
        $errors = 0;
        $lineNumber = 1;

        try {
            /*
             * Détection du séparateur.
             */
            $delimiter = $this->detectDelimiter($handle);

            rewind($handle);

            /*
             * Lecture de l'en-tête.
             */
            $headers = fgetcsv(
                $handle,
                0,
                $delimiter
            );

            $lineNumber++;

            if ($headers === false) {
                return StepResult::failed(
                    __('Le fichier CSV est vide.', MY_AI_AGENT_DOMAIN)
                );
            }

            $headers = $this->normalizeHeaders($headers);

            /*
             * On transforme les noms de colonnes en index.
             */
            $headerMap = $this->buildHeaderMap($headers);

            /*
             * Vérification des colonnes nécessaires.
             */
            $this->validateHeaders($headerMap);

            /*
             * Lecture des lignes.
             */
            while (
                ($row = fgetcsv(
                    $handle,
                    0,
                    $delimiter
                )) !== false
            ) {
                $currentLine = $lineNumber++;

                /*
                 * Ligne vide.
                 */
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                /*
                 * Vérification du nombre de colonnes.
                 */
                if (count($row) !== count($headers)) {
                    $errors++;

                    continue;
                }

                try {
                    $row = $this->normalizeRow($row);

                    /*
                     * Transformation de la ligne CSV
                     * en données métier.
                     */
                    $data = $this->mapRow(
                        $row,
                        $headerMap
                    );

                    /*
                     * Sauvegarde.
                     *
                     * Le repository décide automatiquement
                     * s'il s'agit d'un INSERT ou d'un UPDATE.
                     */
                    $result = $this->importRowRepository->save(
                        $importId,
                        $data
                    );

                    if ($result->inserted) {
                        $inserted++;
                    } elseif ($result->updated) {
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                } catch (Throwable $exception) {
                    $errors++;

                    /*
                     * On continue avec les lignes suivantes.
                     */
                    continue;
                }
            }

            /*
             * Mise à jour de l'import.
             */
            $this->importRepository->updateCounters(
                $importId,
                $inserted,
                $updated,
                $unchanged,
                $errors
            );

            /*
             * S'il y a des erreurs, le step échoue.
             */
            if ($errors > 0) {
                return StepResult::failed(
                    sprintf(
                        __(
                            'Import terminé avec des erreurs. Insérés : %d, mis à jour : %d, inchangés : %d, erreurs : %d.',
                            MY_AI_AGENT_DOMAIN
                        ),
                        $inserted,
                        $updated,
                        $unchanged,
                        $errors
                    )
                );
            }

            return StepResult::continue(
                [
                    'message' => sprintf(
                        __(
                            'Import terminé. Insérés : %d, mis à jour : %d, inchangés : %d.',
                            MY_AI_AGENT_DOMAIN
                        ),
                        $inserted,
                        $updated,
                        $unchanged
                    ),

                    'import_id' => $importId,

                    'inserted' => $inserted,

                    'updated' => $updated,

                    'unchanged' => $unchanged,

                    'errors' => $errors,
                ]
            );
        } catch (Throwable $exception) {
            /*
             * Erreur globale pendant l'import.
             */
            $this->importRepository->markError(
                $importId,
                $exception->getMessage()
            );

            return StepResult::failed(
                $exception->getMessage()
            );
        } finally {
            fclose($handle);
        }
    }

    /**
     * Détecte le séparateur CSV.
     */
    private function detectDelimiter($handle): string
    {
        $line = fgets($handle);

        if ($line === false) {
            return ',';
        }

        $candidates = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($candidates);

        return (string) array_key_first($candidates);
    }

    /**
     * @param array<int, string|null> $headers
     *
     * @return array<int, string>
     */
    private function normalizeHeaders(
        array $headers
    ): array {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $value = (string) $header;

            /*
             * Suppression du BOM UTF-8.
             */
            if ($index === 0) {
                $value = preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    $value
                ) ?? $value;
            }

            $normalized[] = trim($value);
        }

        return $normalized;
    }

    /**
     * @param array<int, string> $headers
     *
     * @return array<string, int>
     */
    private function buildHeaderMap(
        array $headers
    ): array {
        $map = [];

        foreach ($headers as $index => $header) {
            $map[$header] = $index;
        }

        return $map;
    }

    /**
     * Vérifie les colonnes nécessaires.
     *
     * @param array<string, int> $headerMap
     */
    private function validateHeaders(
        array $headerMap
    ): void {
        $requiredHeaders = [
            'Reference',
            'Libelle produit (fr)',
            'Code famille remise',
            'Nom famille remise',
            'Code EAN',
            'Prix promotion',
            'Prix',
        ];

        foreach ($requiredHeaders as $header) {
            if (!isset($headerMap[$header])) {
                throw new InvalidArgumentException(
                    sprintf(
                        __(
                            'La colonne "%s" est absente du CSV.',
                            MY_AI_AGENT_DOMAIN
                        ),
                        $header
                    )
                );
            }
        }
    }

    /**
     * @param array<int, string|null> $row
     *
     * @return array<int, string>
     */
    private function normalizeRow(
        array $row
    ): array {
        return array_map(
            static function ($value): string {
                $value = trim((string) $value);

                $value = str_replace(
                    [
                        "\xC2\xA0",
                        "\xE2\x80\xAF",
                    ],
                    ' ',
                    $value
                );

                return trim($value);
            },
            $row
        );
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isEmptyRow(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Transforme une ligne CSV en données métier.
     *
     * @param array<int, string> $row
     * @param array<string, int> $headerMap
     *
     * @return array<string, mixed>
     */
    private function mapRow(
        array $row,
        array $headerMap
    ): array {
        $reference = $this->getValue(
            $row,
            $headerMap,
            'Reference'
        );

        if ($reference === '') {
            throw new InvalidArgumentException(
                __('La référence est obligatoire.', MY_AI_AGENT_DOMAIN)
            );
        }

        $promotionPrice = $this->getValue(
            $row,
            $headerMap,
            'Prix promotion'
        );

        $price = $this->getValue(
            $row,
            $headerMap,
            'Prix'
        );

        return [
            'reference' => $reference,

            'label' => $this->getValue(
                $row,
                $headerMap,
                'Libelle produit (fr)'
            ),

            'family_code' => $this->getValue(
                $row,
                $headerMap,
                'Code famille remise'
            ),

            'family_name' => $this->getValue(
                $row,
                $headerMap,
                'Nom famille remise'
            ),

            'ean' => $this->getValue(
                $row,
                $headerMap,
                'EAN'
            ) ?: null,

            'promotion_price' =>
                $this->normalizePrice(
                    $promotionPrice
                ),

            'price' =>
                $this->normalizePrice(
                    $price
                ),

            'status' => 'pending',

            'error' => null,
        ];
    }

    /**
     * @param array<int, string> $row
     * @param array<string, int> $headerMap
     */
    private function getValue(
        array $row,
        array $headerMap,
        string $column
    ): string {
        if (!isset($headerMap[$column])) {
            return '';
        }

        return trim(
            (string) (
                $row[$headerMap[$column]]
                ?? ''
            )
        );
    }

    private function normalizePrice(
        string $price
    ): ?float {
        if ($price === '') {
            return null;
        }

        $price = str_replace(
            [
                ' ',
                "\xC2\xA0",
                "\xE2\x80\xAF",
            ],
            '',
            $price
        );

        $price = str_replace(
            ',',
            '.',
            $price
        );

        return (float) $price;
    }
}
