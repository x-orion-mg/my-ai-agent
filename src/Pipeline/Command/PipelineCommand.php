<?php


declare(strict_types=1);

namespace MyAIAgent\Pipeline\Command;

use MyAIAgent\Pipeline\Enum\ProductJobType;
use MyAIAgent\Pipeline\Service\PipelineTestService;
use Throwable;

final class PipelineCommand
{
    public function __construct(
        private readonly PipelineTestService $tester,
    )
    {
    }

    /**
     * Teste un ProductJobType à partir d'une ligne import_rows.
     *
     * ## OPTIONS
     *
     * <import-row-id>
     * : ID de la ligne dans import_rows.
     *
     * <type>
     * : source, ai_generation, validation ou woocommerce.
     *
     * [--execute]
     * : Autorise réellement l'écriture WooCommerce.
     *
     * [--format=<format>]
     * : table ou json. Default: table.
     *
     * ## EXAMPLES
     *
     *     wp my-ai-agent pipeline test-row 123 source
     *
     *     wp my-ai-agent pipeline test-row 123 ai_generation
     *
     *     wp my-ai-agent pipeline test-row 123 validation
     *
     *     wp my-ai-agent pipeline test-row 123 woocommerce
     *
     *     wp my-ai-agent pipeline test-row 123 woocommerce --execute
     *
     *     wp my-ai-agent pipeline test-row 123 validation --format=json
     */
    public function test_row(
        array $args,
        array $assocArgs
    ): void
    {
        [$rowId, $type] = $args;

        try {
            $jobType = ProductJobType::from(
                strtolower($type)
            );
        } catch (\ValueError) {
            \WP_CLI::error(
                'Type invalide. Valeurs autorisées : source, ai_generation, validation, woocommerce.'
            );

            return;
        }

        try {
            $result = $this->tester->testRow(
                (int)$rowId,
                $jobType,
                isset($assocArgs['execute']),
            );
        } catch (Throwable $exception) {
            \WP_CLI::error(
                $exception->getMessage()
            );

            return;
        }

        /*
         * Sortie JSON.
         */
        if (
            ($assocArgs['format'] ?? 'table')
            === 'json'
        ) {
            \WP_CLI::log(
                (string)wp_json_encode(
                    $result,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );

            return;
        }

        \WP_CLI::success(
            sprintf(
                'Test %s terminé pour import_row #%d — référence %s.',
                $jobType->value,
                $result['import_row_id'],
                $result['reference'],
            )
        );

        foreach (
            $result['results']
            as $typeName => $step
        ) {
            $status = $step['status'] ?? 'UNKNOWN';

            \WP_CLI::log(
                sprintf(
                    '[%s] %s',
                    $status,
                    strtoupper($typeName)
                )
            );

            if (isset($step['message'])) {
                \WP_CLI::log(
                    '  ' . $step['message']
                );
            }

            if (isset($step['data'])) {
                \WP_CLI::log(
                    (string)wp_json_encode(
                        $step['data'],
                        JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                    )
                );
            }
        }
    }
}