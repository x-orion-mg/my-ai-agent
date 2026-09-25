<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\Agents\Legrand\Response\LegrandCsvValidationResult;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Services\File\FileStorageService;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;

final class ValidateCsvInputStep implements AgentStepInterface
{
    private const array EXPECTED_HEADERS = [
        'Reference',
        'Libelle produit (fr)',
        'Code famille remise',
        'Nom famille remise',
        'Code EAN',
        'Prix promotion',
        'Prix',
    ];
    private const int MAX_ERRORS = 100;

    public function __construct(
        private FileStorageService $fileStorage,
        private ImportRepository $importRepository

    ) {
    }

    public function id(): string
    {
        return 'validate_csv_input';
    }

    public function label(): string
    {
        return __('Validation CSV des paramètres', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $fileCsv = $context->get('legrand_csv');

        if (!is_array($fileCsv)) {
            return StepResult::failed(
                __('Fichier CSV introuvable.', MY_AI_AGENT_DOMAIN)
            );
        }

        $relativePath = $fileCsv['path'] ?? '';

        if ($relativePath === '') {
            return StepResult::failed(
                __('Chemin du fichier CSV introuvable.', MY_AI_AGENT_DOMAIN)
            );
        }

        $filePath = $this->fileStorage->getUploadPath(
            'legrand',
            $fileCsv['name']
        );
        $validationResult = $this->validate(
            $filePath
        );

        if (!$validationResult->valid) {
            if ($filePath !== '') {
                $this->fileStorage->delete($filePath);
            }
            return StepResult::failed(
                $validationResult->errorMessage()
            );
        }

        /*
      * CSV valide.
      *
      * On peut maintenant créer l'import en BDD.
      */
        $importId = $this->importRepository->create(
            source: 'legrand',
            filename: $fileCsv['name'],
            totalRows: $validationResult->totalRows
        );

        if ($importId <= 0) {
            /*
             * Si l'insertion BDD échoue,
             * on supprime également le fichier.
             */
            $this->fileStorage->delete(
                $filePath
            );

            return StepResult::failed(
                __(
                    'Impossible d\'enregistrer l\'import.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        return StepResult::continue([
            'import_id' => $importId,

            'message' => sprintf(
                __(
                    'CSV valide. %d lignes détectées.',
                    MY_AI_AGENT_DOMAIN
                ),
                $validationResult->totalRows
            ),
        ]);

    }
    public function validate(string $filePath): LegrandCsvValidationResult
    {
        if (!is_file($filePath)) {
            return new LegrandCsvValidationResult(
                false,
                0,
                ['Le fichier CSV est introuvable.']
            );
        }

        if (!is_readable($filePath)) {
            return new LegrandCsvValidationResult(
                false,
                0,
                ['Le fichier CSV n’est pas lisible.']
            );
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return new LegrandCsvValidationResult(
                false,
                0,
                ['Impossible d’ouvrir le fichier CSV.']
            );
        }

        try {
            $delimiter = $this->detectDelimiter($handle);

            rewind($handle);

            $headers = fgetcsv(
                $handle,
                0,
                $delimiter
            );

            if ($headers === false) {
                return new LegrandCsvValidationResult(
                    false,
                    0,
                    ['Le fichier CSV est vide.']
                );
            }

            $headers = $this->normalizeHeaders($headers);

            if ($headers !== self::EXPECTED_HEADERS) {
                return new LegrandCsvValidationResult(
                    false,
                    0,
                    [
                        'Les colonnes du CSV sont invalides.',
                        'Colonnes attendues : '
                        . implode(' | ', self::EXPECTED_HEADERS),
                        'Colonnes reçues : '
                        . implode(' | ', $headers),
                    ]
                );
            }

            $totalRows = 0;
            $errors = [];

            $references = [];
            $eans = [];

            while (($row = fgetcsv(
                    $handle,
                    0,
                    $delimiter
                )) !== false) {

                $lineNumber = $totalRows + 2;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $totalRows++;

                if (count($row) !== count(self::EXPECTED_HEADERS)) {
                    $this->addError(
                        $errors,
                        sprintf(
                            'Ligne %d : %d colonnes détectées, %d attendues.',
                            $lineNumber,
                            count($row),
                            count(self::EXPECTED_HEADERS)
                        )
                    );

                    continue;
                }

                $row = $this->normalizeRow($row);

                [
                    $reference,
                    $label,
                    $familyCode,
                    $familyName,
                    $ean,
                    $promotionPrice,
                    $price,
                ] = $row;

                /*
                 * Reference
                 */
                if ($reference === '') {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Reference obligatoire."
                    );
                } elseif (!preg_match(
                    '/^[A-Za-z0-9._-]+$/',
                    $reference
                )) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Reference invalide : {$reference}."
                    );
                } elseif (isset($references[$reference])) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Reference dupliquée : {$reference}."
                    );
                } else {
                    $references[$reference] = true;
                }

                /*
                 * Libellé
                 */
                if ($label === '') {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Libelle produit (fr) obligatoire."
                    );
                }

                /*
                 * Famille
                 */
                if ($familyCode === '') {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Code famille remise obligatoire."
                    );
                } elseif (!preg_match('/^\d+$/', $familyCode)) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Code famille remise invalide."
                    );
                }

                if ($familyName === '') {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Nom famille remise obligatoire."
                    );
                }

                /*
                 * EAN
                 */
                if ($ean !== '') {
                    if (!$this->isValidEan($ean)) {
                        $this->addError(
                            $errors,
                            "Ligne {$lineNumber} : Code EAN invalide : {$ean}."
                        );
                    } elseif (isset($eans[$ean])) {
                        $this->addError(
                            $errors,
                            "Ligne {$lineNumber} : Code EAN dupliqué : {$ean}."
                        );
                    } else {
                        $eans[$ean] = true;
                    }
                }

                /*
                 * Prix promotionnel
                 */
                if (
                    $promotionPrice !== ''
                    && !$this->isValidPrice($promotionPrice)
                ) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Prix promotion invalide : {$promotionPrice}."
                    );
                }

                /*
                 * Prix
                 */
                if (
                    $promotionPrice !== ''
                    && $price === ''
                ) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Prix obligatoire lorsqu'un prix promotionnel est renseigné."
                    );
                } elseif (
                    $price !== ''
                    && !$this->isValidPrice($price)
                ) {
                    $this->addError(
                        $errors,
                        "Ligne {$lineNumber} : Prix invalide : {$price}."
                    );
                }

                /*
                 * Comparaison prix / prix promotionnel
                 */
                if (
                    $promotionPrice !== ''
                    && $price !== ''
                    && $this->isValidPrice($promotionPrice)
                    && $this->isValidPrice($price)
                ) {
                    $normalPrice = $this->normalizePrice($price);
                    $promoPrice = $this->normalizePrice($promotionPrice);

                    if ($normalPrice <= $promoPrice) {
                        $this->addError(
                            $errors,
                            "Ligne {$lineNumber} : Le prix ({$price}) doit être supérieur au prix promotionnel ({$promotionPrice})."
                        );
                    }
                }


                if (count($errors) >= self::MAX_ERRORS) {
                    $errors[] = 'Validation arrêtée : trop d’erreurs.';
                    break;
                }
            }

            if ($totalRows === 0 && $errors === []) {
                $errors[] = 'Le CSV ne contient aucune ligne de données.';
            }

            return new LegrandCsvValidationResult(
                $errors === [],
                $totalRows,
                $errors
            );
        } finally {
            fclose($handle);
        }
    }
    private function normalizePrice(string $price): float
    {
        $price = str_replace(
            [
                ' ',
                "\xC2\xA0",
                "\xE2\x80\xAF",
            ],
            '',
            $price
        );

        $price = str_replace(',', '.', $price);

        return (float) $price;
    }


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
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $value = (string) $header;

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
     * @param array<int, string|null> $row
     * @return array<int, string>
     */
    private function normalizeRow(array $row): array
    {
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
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function isValidEan(string $ean): bool
    {
        return preg_match(
                '/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/',
                $ean
            ) === 1;
    }

    private function isValidPrice(string $price): bool
    {
        $price = str_replace(
            [
                ' ',
                "\xC2\xA0",
                "\xE2\x80\xAF",
            ],
            '',
            $price
        );

        $price = str_replace(',', '.', $price);

        return preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $price
            ) === 1;
    }

    /**
     * @param array<int, string> $errors
     */
    private function addError(
        array &$errors,
        string $message
    ): void {
        if (count($errors) < self::MAX_ERRORS) {
            $errors[] = $message;
        }
    }

}
