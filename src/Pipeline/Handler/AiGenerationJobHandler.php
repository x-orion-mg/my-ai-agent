<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\AI\AIService;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Settings;
use Throwable;

final readonly class AiGenerationJobHandler implements JobHandlerInterface
{
    public function __construct(
        private AIService $ai,
        private BuildPromptStep $promptBuilder,
        private Settings $settings,
    ) {
    }

    public function handle( ProductJob $job ): array {
        $row = $job->payload['import_row'] ?? [];

        $source = $job->payload['source'] ?? null;

        if (!is_array($row)) {
            throw new PermanentJobException(
                'Les données import_row sont absentes du job AI.'
            );
        }

        /*
         * ======================================================
         * 1. SOURCE
         * ======================================================
         */

        if (!is_array($source)) {
            $source = $this->sourceFromRow($row);
        }

        /*
         * ======================================================
         * 2. SI LE CSV EST COMPLET
         *
         * AUCUN APPEL IA.
         * ======================================================
         */

        if ($this->hasCompleteCsvContent($row)) {
            return [
                'ai' => [
                    'text' => $this->buildCsvResponse(
                        $job,
                        $row,
                        $source
                    ),
                    'provider' => 'csv',
                    'model' => 'none',
                    'finishReason' => 'csv_source',
                    'usage' => [
                        'prompt_tokens' => 0,
                        'completion_tokens' => 0,
                        'total_tokens' => 0,
                    ],
                ],
            ];
        }

        /*
         * ======================================================
         * 3. FALLBACK IA
         *
         * Seulement si le contenu CSV est incomplet.
         * ======================================================
         */

        $promptId = (int) ($row['prompt_id'] ?? $this->settings->get('default_prompt_id', 0));

        if ($promptId <= 0) {
            throw new PermanentJobException(
                'Aucun prompt produit n’est configuré.'
            );
        }

        $context = new AgentContext(
            executionId: 'pipeline-' . $job->id,

            input: [
                'reference' =>
                    $job->reference,

                'technical_name' =>
                    $row['label'] ?? '',

                'family_name' =>
                    $row['family_name'] ?? '',

                'ean' =>
                    $row['ean'] ?? '',

                'language' =>
                    $this->settings->get(
                        'language',
                        'fr'
                    ),
            ],

            data: [
                'prompt' => $promptId,

                'product' =>
                    $this->sourceObject($source),
            ],

            ai: $this->ai,
        );

        try {
            $result = $this->promptBuilder->execute($context);
        } catch (Throwable $exception) {
            throw new PermanentJobException(
                'Impossible de construire le prompt : ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if (!$result instanceof StepResult || !$result->shouldContinue()) {
            throw new PermanentJobException(
                'La construction du prompt produit a échoué.'
            );
        }

        $prompt = $result->data()['prompt'] ?? null;

        if (!is_string($prompt) || trim($prompt) === '') {
            throw new PermanentJobException(
                'Le prompt généré est vide.'
            );
        }

        try {
            $response = $this->ai->ask(
                (string) $this->settings->get('default_provider', 'openrouter'),
                $prompt
            );
        } catch (Throwable $exception) {
            throw new RetryableJobException(
                'Erreur du fournisseur IA : ' . $exception->getMessage(),
                previous: $exception
            );
        }

        return [
            'ai' => [
                'text' => $response->text(),
                'provider' => $response->provider(),
                'model' => $response->model(),
                'finishReason' => $response->finishReason(),
                'usage' => $response->usage(),
            ],
        ];
    }

    /**
     * Vérifie si les six champs éditoriaux CSV
     * sont tous présents.
     */
    private function hasCompleteCsvContent(
        array $row
    ): bool {
        $fields = [
            'product_name',
            'short_description',
            'description',
            'category',
            'alt',
            'meta_description',
        ];

        return array_all($fields, fn($field) => isset($row[$field]) && trim((string)$row[$field]) !== '');

    }

    /**
     * Construit la réponse JSON sans IA.
     *
     * @return string
     */
    private function buildCsvResponse(
        ProductJob $job,
        array $row,
        array $source
    ): string {
        $productName = trim(
            (string) $row['product_name']
        );

        $shortDescription = trim(
            (string) $row['short_description']
        );

        $description = trim(
            (string) $row['description']
        );

        $category = trim(
            (string) $row['category']
        );

        $alt = trim(
            (string) $row['alt']
        );

        $metaDescription = trim(
            (string) $row['meta_description']
        );

        /*
         * Transformation :
         *
         * Protection > Disjoncteurs > Divisionnaires
         *
         * devient :
         *
         * [
         *   Protection,
         *   Disjoncteurs,
         *   Divisionnaires
         * ]
         */
        $categories = array_values(
            array_filter(
                array_map(
                    static fn(string $value): string =>
                    trim($value),
                    explode('>', $category)
                ),
                static fn(string $value): bool =>
                    $value !== ''
            )
        );

        $slug = sanitize_title(
            $productName . '-' . $job->reference
        );

        /*
         * Le mot-clé principal est dérivé du nom
         * fourni par le CSV.
         */
        $focusKeyword = $productName;

        /*
         * La source LEGRAND fournit l'image officielle.
         */
        $imageUrl = $source['image'] ?? null;

        $ean = trim(
            (string) ($row['ean'] ?? '')
        );

        $data = [
            'productName' =>
                $productName,

            'shortDescription' =>
                $shortDescription,

            'description' =>
                $description,

            'caracteristiquesTechnique' =>
                $source['technicalData'] ?? '',

            'sku' =>
                $job->reference,

            'ean' =>
                $ean !== ''
                    ? $ean
                    : null,

            'brand' =>
                'Legrand',

            'productType' =>
                null,

            'category' =>
                $category,

            'categories' =>
                $categories,

            /*
             * Ces informations ne sont pas présentes
             * dans le nouveau CSV.
             */
            'tags' => [],

            'attributes' => [],

            /*
             * Les données techniques officielles
             * sont conservées dans source. Elles seront
             * également envoyées à WooCommerce.
             */
            'technicalSpecifications' => [],

            'seo' => [
                'metaTitle' =>
                    $productName,

                'metaDescription' =>
                    $metaDescription,

                'slug' =>
                    $slug,

                'focusKeyword' =>
                    $focusKeyword,

                'keywords' => [],
            ],

            'image' => [
                'url' =>
                    $imageUrl,

                'prompt' =>
                    '',

                'alt' =>
                    $alt,
            ],

            /*
             * Pas de FAQ dans le CSV.
             *
             * On ne demande surtout pas à l'IA
             * d'en inventer.
             */
            'faq' => [],

            'schema' => [
                '@context' =>
                    'https://schema.org',

                '@type' =>
                    'Product',

                'name' =>
                    $productName,

                'description' =>
                    $description,

                'sku' =>
                    $job->reference,

                'gtin' =>
                    $ean !== ''
                        ? $ean
                        : null,

                'brand' => [
                    '@type' =>
                        'Brand',

                    'name' =>
                        'Legrand',
                ],
            ],

            'quality' => [
                'confidence' =>
                    'high',

                'needsReview' =>
                    false,

                'reviewReason' =>
                    null,
            ],
        ];

        $json = wp_json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        if (
            !is_string($json)
            || $json === ''
        ) {
            throw new PermanentJobException(
                'Impossible de construire la réponse JSON depuis le CSV.'
            );
        }

        return $json;
    }

    /**
     * Si le SourceJobHandler a déjà été exécuté,
     * le payload contient normalement source.
     *
     * Cette méthode permet également de reconstruire
     * la source depuis import_rows.
     */
    private function sourceFromRow(
        array $row
    ): array {
        return [
            'reference' =>
                (string) (
                    $row['reference'] ?? ''
                ),

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
                    $row['source_technical_data']
                    ?? null
                ),
        ];
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

    private function sourceObject(
        array $source
    ): object {
        return new class($source) {
            public function __construct(
                private readonly array $data
            ) {
            }

            public function __get(
                string $name
            ): mixed {
                return $this->data[$name] ?? null;
            }
        };
    }
}