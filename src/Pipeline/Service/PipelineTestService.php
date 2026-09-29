<?php


declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use InvalidArgumentException;
use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Enum\ProductJobStatus;
use MyAIAgent\Pipeline\Enum\ProductJobType;
use MyAIAgent\Pipeline\Handler\WooCommerceJobHandler;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use RuntimeException;

/**
 * Exécute les handlers du pipeline en mémoire pour les tests manuels.
 *
 * IMPORTANT :
 *
 * - aucun product_job n'est créé ;
 * - aucun job n'est modifié ;
 * - aucun job suivant n'est programmé ;
 * - aucun cron n'est déclenché.
 */
final readonly class PipelineTestService
{
    /**
     * @param array<string,JobHandlerInterface> $handlers
     */
    public function __construct(
        private ImportRowRepository $rows,
        private array               $handlers,
    )
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function testRow(
        int            $importRowId,
        ProductJobType $target,
        bool           $executeWooCommerce = false,
        bool $bypassAi = false,
    ): array
    {
        $row = $this->rows->find($importRowId);

        if ($row === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Import row #%d introuvable.',
                    $importRowId
                )
            );
        }

        $reference = trim(
            (string)($row['reference'] ?? '')
        );

        if ($reference === '') {
            throw new RuntimeException(
                sprintf(
                    'Import row #%d ne contient aucune référence.',
                    $importRowId
                )
            );
        }

        /*
         * Le payload initial correspond exactement
         * à celui utilisé par PipelineDispatcher.
         */
        $payload = [
            'import_row' => $row,
        ];

        $results = [];

        /*
         * Chaque étape dépend des précédentes.
         *
         * SOURCE
         *
         * SOURCE + AI
         *
         * SOURCE + AI + VALIDATION
         *
         * SOURCE + AI + VALIDATION + WOOCOMMERCE
         */
        foreach ($this->sequenceUntil($target) as $type) {

            $job = new ProductJob(
                id: 0,
                importRowId: $importRowId,
                reference: $reference,
                type: $type,
                status: ProductJobStatus::PENDING,
                attempts: 0,
                maxAttempts: 1,
                availableAt: null,
                payload: $payload,
            );
            if (
                $type === ProductJobType::AI_GENERATION
                && $bypassAi
            ) {
                $data = [
                    'ai' => [
                        'text' => $this->getStaticAiResponse($reference),
                        'provider' => 'test',
                        'model' => 'static',
                        'finishReason' => 'stop',
                        'usage' => [
                            'prompt_tokens' => 0,
                            'completion_tokens' => 0,
                            'total_tokens' => 0,
                            'cost' => 0,
                        ],
                    ],
                ];

                $payload = array_replace(
                    $payload,
                    $data
                );

                $results[$type->value] = [
                    'status' => 'BYPASS',
                    'data' => $data,
                ];

                continue;
            }

            $handler = $this->handlers[$type->value] ?? null;

            if (!$handler instanceof JobHandlerInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Aucun handler configuré pour %s.',
                        $type->value
                    )
                );
            }



            /*
             * WooCommerce :
             *
             * Par défaut on ne crée/modifie PAS le produit.
             *
             * Il faut explicitement utiliser --execute.
             */
            if (
                $type === ProductJobType::WOOCOMMERCE
                && !$executeWooCommerce
            ) {
                if (!$handler instanceof WooCommerceJobHandler) {
                    throw new RuntimeException(
                        'Le handler WooCommerce ne permet pas le mode dry-run.'
                    );
                }

                $prepared = $handler->prepare($job);

                $results[$type->value] = [
                    'status' => 'DRY_RUN',
                    'data' => $prepared,
                    'message' => 'WooCommerce non exécuté. Utilisez --execute pour créer/modifier le produit.',
                ];

                $payload = array_replace(
                    $payload,
                    [
                        'woocommerce_test' => $prepared,
                    ]
                );

                break;
            }

            /*
             * Exécution réelle du handler.
             */
            $data = $handler->handle($job);

            /*
             * Le résultat devient le payload
             * de l'étape suivante.
             */
            $payload = array_replace(
                $payload,
                $data
            );

            $results[$type->value] = [
                'status' => 'OK',
                'data' => $data,
            ];
        }

        return [
            'import_row_id' => $importRowId,
            'reference' => $reference,
            'target' => $target->value,
            'results' => $results,
            'payload' => $payload,
        ];
    }

    /**
     * @return list<ProductJobType>
     */
    private function sequenceUntil(
        ProductJobType $target
    ): array
    {
        $sequence = [
            ProductJobType::SOURCE,
            ProductJobType::AI_GENERATION,
            ProductJobType::VALIDATION,
            ProductJobType::WOOCOMMERCE,
        ];

        $index = array_search(
            $target,
            $sequence,
            true
        );

        if ($index === false) {
            throw new InvalidArgumentException(
                'Type de job inconnu.'
            );
        }

        return array_slice(
            $sequence,
            0,
            $index + 1
        );
    }

    private function getStaticAiResponse(
        string $reference
    ): string {
        return json_encode(
            [
                'productName' => "Produit de test LEGRAND {$reference}",
                'shortDescription' => '<p>Description courte de test.</p>',
                'description' => '<p>Description complète de test.</p>',

                'caracteristiquesTechnique' => '<table><tbody>
                <tr>
                    <th>Rated current</th>
                    <td>6 A</td>
                </tr>
                <tr>
                    <th>Rated voltage</th>
                    <td>230 V</td>
                </tr>
            </tbody></table>',

                'sku' => $reference,
                'ean' => '3414970366634',
                'brand' => 'Legrand',

                'productType' => 'Disjoncteur divisionnaire',

                'category' => 'Protection & Sécurité Électrique > Disjoncteurs > Disjoncteurs divisionnaires',

                'categories' => [
                    'Protection & Sécurité Électrique',
                    'Disjoncteurs',
                    'Disjoncteurs divisionnaires',
                ],

                'tags' => [],

                'attributes' => [
                    [
                        'name' => 'Courbe de déclenchement',
                        'options' => ['B'],
                    ],
                    [
                        'name' => 'Intensité nominale',
                        'options' => ['6 A'],
                    ],
                    [
                        'name' => 'Tension nominale',
                        'options' => ['230 V'],
                    ],
                    [
                        'name' => 'Nombre de pôles',
                        'options' => ['1P'],
                    ],
                ],

                'technicalSpecifications' => [
                    [
                        'name' => 'Rated current',
                        'value' => '6 A',
                    ],
                    [
                        'name' => 'Rated voltage',
                        'value' => '230 V',
                    ],
                    [
                        'name' => 'Mounting method',
                        'value' => 'DIN rail',
                    ],
                ],

                'seo' => [
                    'metaTitle' => "Produit LEGRAND {$reference}",
                    'metaDescription' => "Produit LEGRAND référence {$reference}.",
                    'slug' => "produit-legrand-{$reference}",
                    'focusKeyword' => "LEGRAND {$reference}",
                    'keywords' => [
                        'Legrand',
                        $reference,
                    ],
                ],

                'image' => [
                    'url' => '',
                    'prompt' => "Product photograph LEGRAND {$reference}",
                    'alt' => "Produit LEGRAND {$reference}",
                ],

                'faq' => [],

                'schema' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => "Produit LEGRAND {$reference}",
                    'sku' => $reference,
                    'gtin' => '3414970366634',
                    'brand' => [
                        '@type' => 'Brand',
                        'name' => 'Legrand',
                    ],
                    'description'=> 'Disjoncteur divisionnaire monophasé LEGRAND RX3 1P 6A courbe B, pouvoir de coupure 6 kA, montage sur rail DIN, IP20, IK02.'
                ],

                'quality' => [
                    'confidence' => 'high',
                    'needsReview' => false,
                    'reviewReason' => null,
                ],
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );
    }
}