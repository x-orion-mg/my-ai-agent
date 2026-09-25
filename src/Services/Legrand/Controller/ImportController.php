<?php


declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Controller;

use MyAIAgent\Services\File\FileStorageService;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;
use MyAIAgent\Execution\ExecutionManager;
use Throwable;

final readonly class ImportController
{
    public function __construct(
        private ImportRepository $importRepository,
        private ExecutionManager $executionManager,
        private FileStorageService $fileStorage,
    )
    {
    }

    /**
     * Retourne la liste des imports.
     */
    public function index(): void
    {
        $this->guard();

        try {
            $imports = $this->importRepository->findAll();

            $this->success([
                'imports' => $imports,
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                500
            );
        }
    }

    /**
     * Lance la synchronisation d'un import.
     */
    public function synchronize(): void
    {
        $this->guard();

        $importId = isset($_POST['import_id'])
            ? absint($_POST['import_id'])
            : 0;

        if ($importId <= 0) {
            $this->fail(
                __('Import invalide.', MY_AI_AGENT_DOMAIN),
                400
            );
        }

        try {
            $import = $this->importRepository->find($importId);

            if ($import === null) {
                $this->fail(
                    __('Import introuvable.', MY_AI_AGENT_DOMAIN),
                    404
                );
            }

            /*
             * Vérifie que l'import peut être synchronisé.
             */
            $status = (string)($import['status'] ?? '');

            if (!in_array(
                $status,
                [
                    'uploaded',
                    'completed',
                ],
                true
            )) {
                $this->fail(
                    sprintf(
                        __(
                            'Cet import ne peut pas être synchronisé dans son état actuel : %s.',
                            MY_AI_AGENT_DOMAIN
                        ),
                        $status
                    ),
                    400
                );
            }

            /*
             * Création de l'exécution.
             *
             * L'agent pourra ensuite récupérer import_id
             * dans son AgentContext.
             */
            $execution = $this->executionManager->create(
                agentId: 'legrand-import',
                input: [
                    'import_id' => $importId,
                    'source' => $import['source'],
                    'filename' => $import['filename'],
                ]
            );

            $steps = $this->executionManager->stepDefinitions(
                $execution
            );

            $this->success([
                'message' => __(
                    'Synchronisation lancée.',
                    MY_AI_AGENT_DOMAIN
                ),

                'execution_id' => $execution->id(),

                'agent' => $execution->agentId(),

                'status' => $execution->status(),

                'current_step' => $execution->currentStep(),

                'steps' => $steps,

                'data' => $execution->data(),

                'error' => $execution->error(),
            ]);
        } catch (Throwable $exception) {
            $this->fail(
                $exception->getMessage(),
                500
            );
        }
    }

    public function download(): void
    {
        $this->guard();

        $importId = isset($_GET['import_id'])
            ? absint($_GET['import_id'])
            : 0;

        if ($importId <= 0) {
            wp_die(
                esc_html__(
                    'Import invalide.',
                    MY_AI_AGENT_DOMAIN
                ),
                '',
                [
                    'response' => 400,
                ]
            );
        }

        $import = $this->importRepository->find($importId);

        if ($import === null) {
            wp_die(
                esc_html__(
                    'Import introuvable.',
                    MY_AI_AGENT_DOMAIN
                ),
                '',
                [
                    'response' => 404,
                ]
            );
        }

        $filename = (string) ($import['filename'] ?? '');

        if ($filename === '') {
            wp_die(
                esc_html__(
                    'Fichier introuvable.',
                    MY_AI_AGENT_DOMAIN
                ),
                '',
                [
                    'response' => 404,
                ]
            );
        }

        try {
            $filePath = $this->fileStorage->getPath(
                'legrand',
                $filename
            );
        } catch (Throwable $exception) {
            wp_die(
                esc_html__(
                    'Fichier introuvable.',
                    MY_AI_AGENT_DOMAIN
                ),
                '',
                [
                    'response' => 404,
                ]
            );
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            wp_die(
                esc_html__(
                    'Le fichier CSV est introuvable.',
                    MY_AI_AGENT_DOMAIN
                ),
                '',
                [
                    'response' => 404,
                ]
            );
        }

        /*
         * Nettoyage des buffers éventuels.
         */
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $downloadName = basename($filename);

        header(
            'Content-Type: text/csv; charset=utf-8'
        );

        header(
            'Content-Disposition: attachment; filename="' .
            rawurlencode($downloadName) .
            '"'
        );

        header(
            'Content-Length: ' . filesize($filePath)
        );

        header(
            'Cache-Control: private, no-store, no-cache, must-revalidate'
        );

        readfile($filePath);

        exit;
    }


    /**
     * Vérification AJAX.
     */
    private function guard(): void
    {
        /*
         * Vérification du nonce.
         */
        check_ajax_referer(
            'my_ai_agent_nonce',
            'nonce'
        );

        /*
         * Vérification des permissions.
         *
         * À adapter selon les capacités de ton plugin.
         */
        if (!current_user_can('manage_options')) {
            $this->fail(
                __(
                    'Vous n’avez pas les permissions nécessaires.',
                    MY_AI_AGENT_DOMAIN
                ),
                403
            );
        }
    }

    /**
     * Réponse AJAX réussie.
     *
     * @param array<string, mixed> $data
     */
    private function success(array $data = []): never
    {
        wp_send_json_success($data);
    }

    /**
     * Réponse AJAX en erreur.
     */
    private function fail(
        string $message,
        int    $status = 400
    ): never
    {
        wp_send_json_error(
            [
                'message' => $message,
            ],
            $status
        );
    }
}
