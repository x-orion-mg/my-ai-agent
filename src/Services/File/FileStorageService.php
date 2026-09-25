<?php

declare(strict_types=1);

namespace MyAIAgent\Services\File;

use RuntimeException;

final class FileStorageService
{
    /**
     * Dossier racine de stockage dans wp-content/uploads.
     */
    private const string BASE_DIRECTORY = 'my-ai-agent';

    /**
     * Sauvegarde un fichier uploadé.
     *
     * Exemple :
     *
     * saveUpload($file, 'legrand');
     *
     * donnera :
     *
     * wp-content/uploads/
     * └── my-ai-agent/
     *     └── legrand/
     *         └── exemple-product-legrand-20260925-170430.csv
     *
     * @param array<string, mixed> $file
     *
     * @return array<string, mixed>
     */
    public function saveUpload(
        array $file,
        string $subdirectory = ''
    ): array {
        /*
         * Fichier temporaire.
         */
        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '') {
            throw new RuntimeException(
                __(
                    'Le fichier temporaire est introuvable.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        /*
         * Vérifie que le fichier provient bien d'un upload HTTP.
         */
        if (!is_uploaded_file($tmpName)) {
            throw new RuntimeException(
                __(
                    'Le fichier uploadé est invalide.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        /*
         * Nom original.
         */
        $originalName = (string) ($file['name'] ?? '');

        if ($originalName === '') {
            throw new RuntimeException(
                __(
                    'Le nom du fichier est invalide.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        /*
         * Répertoire de destination.
         *
         * Exemple :
         *
         * my-ai-agent/legrand
         */
        $uploadDirectory = $this->getUploadDirectory(
            $subdirectory
        );

        /*
         * Crée le répertoire si nécessaire.
         */
        $this->ensureDirectory(
            $uploadDirectory
        );

        /*
         * Génère le nouveau nom.
         *
         * Exemple :
         *
         * exemple-product-legrand-20260925-170430.csv
         */
        $filename = $this->generateFilename(
            $originalName
        );

        /*
         * Évite les collisions.
         */
        $filename = wp_unique_filename(
            $uploadDirectory,
            $filename
        );

        /*
         * Chemin final.
         */
        $destination = trailingslashit(
                $uploadDirectory
            ) . $filename;

        /*
         * Déplace le fichier temporaire.
         */
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

        /*
         * Chemin relatif par rapport à wp-content/uploads.
         *
         * Exemple :
         *
         * my-ai-agent/legrand/fichier.csv
         */
        $relativePath = $this->buildRelativePath(
            $subdirectory,
            $filename
        );

        return [
            'name' => $filename,

            'original_name' => $originalName,

            'path' => $destination,

            'relative_path' => $relativePath,

            'url' => $this->getUploadUrl(
                $relativePath
            ),

            'size' => (int) filesize(
                $destination
            ),

            'mime_type' => $this->detectMimeType(
                $destination
            ),

            'extension' => strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            ),
        ];
    }

    /**
     * Supprime un fichier.
     *
     * Ne lève pas d'erreur si le fichier n'existe déjà plus.
     */
    public function delete(
        string $path
    ): void {
        if ($path === '') {
            return;
        }

        if (!is_file($path)) {
            return;
        }

        if (!unlink($path)) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible de supprimer le fichier "%s".',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $path
                )
            );
        }
    }

    /**
     * Vérifie si un fichier existe.
     */
    public function exists(
        string $path
    ): bool {
        return $path !== ''
            && is_file($path);
    }

    /**
     * Retourne le répertoire physique d'un sous-dossier.
     *
     * Exemple :
     *
     * getUploadDirectory('legrand')
     *
     * retourne :
     *
     * /.../wp-content/uploads/my-ai-agent/legrand
     */
    public function getUploadDirectory(
        string $subdirectory = ''
    ): string {
        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible d’accéder au répertoire des uploads : %s',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $uploads['error']
                )
            );
        }

        $relativeDirectory = self::BASE_DIRECTORY;

        if ($subdirectory !== '') {
            $relativeDirectory .= '/'
                . trim(
                    $subdirectory,
                    '/\\'
                );
        }

        return trailingslashit(
                $uploads['basedir']
            ) . $relativeDirectory;
    }

    /**
     * Retourne le chemin physique d'un fichier.
     *
     * Exemple :
     *
     * getUploadPath(
     *     'legrand',
     *     'produits.csv'
     * )
     *
     * retourne :
     *
     * /.../wp-content/uploads/my-ai-agent/legrand/produits.csv
     */
    public function getUploadPath(
        string $subdirectory,
        string $filename
    ): string {
        $relativePath = $this->buildRelativePath(
            $subdirectory,
            $filename
        );

        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible d’accéder au répertoire des uploads : %s',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $uploads['error']
                )
            );
        }

        return trailingslashit(
                $uploads['basedir']
            ) . ltrim(
                $relativePath,
                '/\\'
            );
    }

    /**
     * Retourne l'URL publique d'un fichier.
     *
     * Exemple :
     *
     * https://example.com/wp-content/uploads/
     * my-ai-agent/legrand/produits.csv
     */
    public function getUploadUrl(
        string $relativePath
    ): string {
        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible d’accéder au répertoire des uploads : %s',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $uploads['error']
                )
            );
        }

        return trailingslashit(
                $uploads['baseurl']
            ) . ltrim(
                $relativePath,
                '/\\'
            );
    }

    /**
     * Transforme un chemin physique en chemin relatif.
     *
     * Exemple :
     *
     * /.../uploads/my-ai-agent/legrand/file.csv
     *
     * devient :
     *
     * my-ai-agent/legrand/file.csv
     */
    public function getRelativePath(
        string $absolutePath
    ): string {
        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible d’accéder au répertoire des uploads : %s',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $uploads['error']
                )
            );
        }

        $basePath = wp_normalize_path(
            trailingslashit(
                $uploads['basedir']
            )
        );

        $absolutePath = wp_normalize_path(
            $absolutePath
        );

        if (
            strpos(
                $absolutePath,
                $basePath
            ) !== 0
        ) {
            throw new RuntimeException(
                __(
                    'Le fichier ne se trouve pas dans le répertoire des uploads.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        return ltrim(
            substr(
                $absolutePath,
                strlen($basePath)
            ),
            '/'
        );
    }

    /**
     * Retourne les informations d'un fichier.
     *
     * @return array<string, mixed>
     */
    public function getFileInfo(
        string $path
    ): array {
        if (!$this->exists($path)) {
            throw new RuntimeException(
                __(
                    'Le fichier est introuvable.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        $filename = basename(
            $path
        );

        $relativePath = $this->getRelativePath(
            $path
        );

        return [
            'name' => $filename,

            'path' => $path,

            'relative_path' => $relativePath,

            'url' => $this->getUploadUrl(
                $relativePath
            ),

            'size' => (int) filesize(
                $path
            ),

            'mime_type' => $this->detectMimeType(
                $path
            ),

            'extension' => strtolower(
                pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                )
            ),
        ];
    }

    /**
     * Génère un nom de fichier avec la date et l'heure.
     *
     * Exemple :
     *
     * exemple-product-legrand.csv
     *
     * devient :
     *
     * exemple-product-legrand-20260925-170430.csv
     */
    public function generateFilename(
        string $originalName
    ): string {
        $originalName = sanitize_file_name(
            $originalName
        );

        if ($originalName === '') {
            throw new RuntimeException(
                __(
                    'Le nom du fichier est invalide.',
                    MY_AI_AGENT_DOMAIN
                )
            );
        }

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );

        $basename = pathinfo(
            $originalName,
            PATHINFO_FILENAME
        );

        $basename = sanitize_file_name(
            $basename
        );

        if ($basename === '') {
            $basename = 'file';
        }

        /*
         * Date/heure WordPress.
         *
         * Exemple :
         *
         * 20260925-170430
         */
        $timestamp = current_time(
            'Ymd-His'
        );

        if ($extension !== '') {
            return sprintf(
                '%s-%s.%s',
                $basename,
                $timestamp,
                $extension
            );
        }

        return sprintf(
            '%s-%s',
            $basename,
            $timestamp
        );
    }

    /**
     * Crée un répertoire s'il n'existe pas.
     */
    private function ensureDirectory(
        string $directory
    ): void {
        if (is_dir($directory)) {
            return;
        }

        if (!wp_mkdir_p($directory)) {
            throw new RuntimeException(
                sprintf(
                    __(
                        'Impossible de créer le répertoire "%s".',
                        MY_AI_AGENT_DOMAIN
                    ),
                    $directory
                )
            );
        }
    }

    /**
     * Construit le chemin relatif depuis le dossier uploads.
     *
     * Exemple :
     *
     * legrand
     * +
     * fichier.csv
     *
     * donne :
     *
     * my-ai-agent/legrand/fichier.csv
     */
    private function buildRelativePath(
        string $subdirectory,
        string $filename
    ): string {
        $path = self::BASE_DIRECTORY;

        if ($subdirectory !== '') {
            $path .= '/'
                . trim(
                    $subdirectory,
                    '/\\'
                );
        }

        $path .= '/'
            . ltrim(
                $filename,
                '/\\'
            );

        return $path;
    }

    /**
     * Détecte le type MIME réel du fichier.
     */
    private function detectMimeType(
        string $path
    ): string {
        if (!is_file($path)) {
            return '';
        }

        $finfo = new \finfo(
            FILEINFO_MIME_TYPE
        );

        $mimeType = $finfo->file(
            $path
        );

        return $mimeType !== false
            ? $mimeType
            : '';
    }

    public function getPath(
        string $directory,
        string $filename
    ): string {
        $filename = basename($filename);

        if ($filename === '') {
            throw new RuntimeException(
                'Nom de fichier invalide.'
            );
        }

        $uploads = wp_upload_dir();

        if (!empty($uploads['error'])) {
            throw new RuntimeException(
                (string) $uploads['error']
            );
        }

        return trailingslashit($uploads['basedir'])
            . self::BASE_DIRECTORY
            . '/'
            . trim($directory, '/')
            . '/'
            . $filename;
    }

}
