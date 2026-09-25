<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Legrand;

use finfo;
use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\Agents\Legrand\Steps\ImportRowsStep;
use MyAIAgent\Agent\Agents\Legrand\Steps\ValidateCsvInputStep;
use MyAIAgent\Services\File\FileStorageService;
use MyAIAgent\Agent\AgentStepInterface;



final readonly class LegrandAgent implements AgentInterface
{

    public function __construct(
        private ValidateCsvInputStep $validateInputStep,
        private  FileStorageService $fileStorage,
        private ImportRowsStep $importRowsStep,

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
            $this->importRowsStep,
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
         $input['legrand_csv']= $this->fileStorage->saveUpload(
             $input['legrand_csv'],
             'legrand'
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

}
