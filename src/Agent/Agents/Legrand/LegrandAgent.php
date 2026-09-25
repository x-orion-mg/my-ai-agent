<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand;

use finfo;
use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\Agents\Legrand\Steps\ValidateCsvInputStep;
use MyAIAgent\Agent\AgentStepInterface;
use RuntimeException;


final readonly class LegrandAgent implements AgentInterface
{

    public function __construct(
        private ValidateCsvInputStep $validateInputStep,

    ) {
    }
    public function id(): string
    {
        return 'legrand-import';
    }

    public function name(): string
    {
        return __('Import catalogue Legrand', MY_AI_AGENT_DOMAIN);
    }

    /**
     * @return array<string, mixed>
     */
    public function formSchema(): array
    {
        return [
            'legrand_csv' => [
                'type' => 'file',
                'required' => true,
            ],
        ];
    }

    /**
     * @return array<int, AgentStepInterface>
     */
    public function steps(): array
    {
        return [
            $this->validateInputStep,
        ];
    }


    /**
     * Validation minimale avant création de l'exécution.
     *
     * @param array<string, mixed> $input
     */
    public function validate(array &$input): array
    {
        $this->validateRequiredCsv(

            $input,
            'legrand_csv'
        );
         $input['legrand_csv']= $this->saveFile(
            $input['legrand_csv']
        );

         return $input;
    }

    private function validateRequiredCsv(
        array $input,
        string $field = 'csv_file'
    ): void {
        $file = $input[$field] ?? null;

        /*
         * Vérifie que le fichier existe.
         */
        if (!is_array($file)) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" est obligatoire.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie l'erreur d'upload.
         */
        if (
            !isset($file['error'])
            || (int) $file['error'] !== UPLOAD_ERR_OK
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" n\'a pas pu être envoyé.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie le fichier temporaire.
         */
        $tmpName = (string) ($file['tmp_name'] ?? '');

        if (
            $tmpName === ''
            || !is_uploaded_file($tmpName)
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" est invalide.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie que le fichier n'est pas vide.
         */
        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" est vide.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie le nom du fichier.
         */
        $filename = (string) ($file['name'] ?? '');

        if ($filename === '') {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" est invalide.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie l'extension.
         */
        $extension = strtolower(
            pathinfo(
                $filename,
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== 'csv') {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" doit être un fichier CSV.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }

        /*
         * Vérifie le type MIME réel du fichier.
         */
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mimeType = $finfo->file($tmpName);

        $allowedMimeTypes = [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
        ];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    __('Le fichier "%s" doit être un fichier CSV valide.', MY_AI_AGENT_DOMAIN),
                    $field
                )
            );
        }
    }

    private function saveFile(array $file): array
    {
        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                sprintf(
                    __('Impossible d’accéder au répertoire des uploads : %s', MY_AI_AGENT_DOMAIN),
                    $uploads['error']
                )
            );
        }

        $directory = trailingslashit(
                $uploads['basedir']
            ) . 'my-ai-agent/legrand';

        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException(
                __(
                    'Impossible de créer le répertoire de stockage du fichier.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        $originalName = (string) ($file['name'] ?? '');

        $extension = strtolower(
            pathinfo($originalName, PATHINFO_EXTENSION)
        );

        $basename = pathinfo(
            $originalName,
            PATHINFO_FILENAME
        );

        /*
         * Nom basé sur la date et l'heure de l'upload.
         *
         * Exemple :
         * exemple-product-legrand-20260925-123745.csv
         */
        $filename = sprintf(
            '%s-%s.%s',
            sanitize_file_name($basename),
            current_time('Ymd-His'),
            $extension
        );

        if ($filename === '') {
            throw new RuntimeException(
                __(
                    'Le nom du fichier est invalide.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        /*
         * Évite d'écraser un fichier existant.
         */
        $filename = wp_unique_filename(
            $directory,
            $filename
        );

        $destination = trailingslashit($directory) . $filename;

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if (!move_uploaded_file(
            $tmpName,
            $destination
        )) {
            throw new RuntimeException(
                __(
                    'Impossible de sauvegarder le fichier.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        return [
            'name' => $filename,
            'original_name' => $originalName,
            'path' => $destination,
            'size' => (int) filesize($destination),
            'type' => (string) ($file['type'] ?? ''),
        ];
    }

}
