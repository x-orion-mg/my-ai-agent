<?php

declare(strict_types=1);

namespace MyAIAgent\Pipeline\Service;

use MyAIAgent\AI\AIService;
use MyAIAgent\Agent\Agents\Product\Response\ProductResponseValidator;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep;
use MyAIAgent\Agent\Response\AiResponseParser;
use MyAIAgent\Core\Container;
use MyAIAgent\Pipeline\Handler\AiGenerationJobHandler;
use MyAIAgent\Pipeline\Handler\SourceJobHandler;
use MyAIAgent\Pipeline\Handler\ValidationJobHandler;
use MyAIAgent\Pipeline\Handler\WooCommerceJobHandler;
use MyAIAgent\Pipeline\Repository\ProductJobRepository;
use MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use MyAIAgent\Services\Legrand\Extractor\ProductImageExtractor;
use MyAIAgent\Services\Legrand\Extractor\ProductTechnicalDataExtractor;
use MyAIAgent\Services\Legrand\Extractor\ProductUrlExtractor;
use MyAIAgent\Services\Legrand\Product\LegrandProductService;
use MyAIAgent\Services\Legrand\Repository\ImportRepository;
use MyAIAgent\Services\Legrand\Repository\ImportRowRepository;
use MyAIAgent\Services\Legrand\Support\LegrandUrl;
use MyAIAgent\Services\Product\ProductService;
use MyAIAgent\Services\Settings;

final readonly class PipelineService
{
    private ProductJobRepository $jobs;

    private ProductJobScheduler $scheduler;

    private ProductJobService $jobService;

    private PipelineDispatcher $dispatcher;

    private PipelineRecoveryService $recovery;

    private RetryPolicy $retryPolicy;

    private RateLimiter $rateLimiter;
    private PipelineTestService $tester;
    public function __construct(
        private Container $container
    ) {
        /*
         * ======================================================
         * 1. REPOSITORY JOBS
         * ======================================================
         */

        $this->jobs = new ProductJobRepository();


        /*
         * ======================================================
         * 2. SCHEDULER
         * ======================================================
         */

        $this->scheduler = new ProductJobScheduler();


        /*
         * ======================================================
         * 3. RETRY POLICY
         * ======================================================
         */

        $this->retryPolicy = new RetryPolicy();


        /*
         * ======================================================
         * 4. RATE LIMITER
         * ======================================================
         */

        $this->rateLimiter = new RateLimiter();


        /*
         * ======================================================
         * 5. SERVICES EXISTANTS
         * ======================================================
         */

        $settings = $this->container->get(
            Settings::class
        );

        $ai = $this->container->get(
            AIService::class
        );

        $productService = $this->container->get(
            ProductService::class
        );

        $promptBuilder = $this->container->get(
            BuildPromptStep::class
        );

        $responseParser = $this->container->get(
            AiResponseParser::class
        );

        $responseValidator = $this->container->get(
            ProductResponseValidator::class
        );

        $imports = $this->container->get(
            ImportRepository::class
        );

        $rows = $this->container->get(
            ImportRowRepository::class
        );


        /*
         * ======================================================
         * 6. LEGRAND PRODUCT SERVICE
         *
         * LegrandProductService n'est actuellement pas enregistré
         * dans le Container principal.
         *
         * On construit ici ses dépendances.
         * ======================================================
         */

        $legrandHttp = new LegrandHttpClient();

        $urlExtractor = new ProductUrlExtractor(
            $legrandHttp
        );

        $imageExtractor = new ProductImageExtractor();

        $technicalDataExtractor = new ProductTechnicalDataExtractor();

        $legrandUrl = new LegrandUrl(
            $legrandHttp
        );

        $legrand = new LegrandProductService(
            $legrandHttp,
            $urlExtractor,
            $imageExtractor,
            $technicalDataExtractor,
            $legrandUrl
        );


        /*
         * ======================================================
         * 7. HANDLER SOURCE
         * ======================================================
         */

        $sourceHandler = new SourceJobHandler(
            $legrand,
            $rows
        );


        /*
         * ======================================================
         * 8. HANDLER AI
         * ======================================================
         */

        $aiHandler = new AiGenerationJobHandler(
            $ai,
            $promptBuilder,
            $settings
        );


        /*
         * ======================================================
         * 9. HANDLER VALIDATION
         * ======================================================
         */

        $validationHandler = new ValidationJobHandler(
            $responseParser,
            $responseValidator
        );


        /*
         * ======================================================
         * 10. HANDLER WOOCOMMERCE
         * ======================================================
         */

        $woocommerceHandler = new WooCommerceJobHandler(
            $productService
        );


        /*
         * ======================================================
         * 11. PRODUCT JOB SERVICE
         *
         * Association :
         *
         * source
         *     -> SourceJobHandler
         *
         * ai
         *     -> AiGenerationJobHandler
         *
         * validation
         *     -> ValidationJobHandler
         *
         * woocommerce
         *     -> WooCommerceJobHandler
         * ======================================================
         */

        $this->jobService = new ProductJobService(
            $this->jobs,
            $this->retryPolicy,
            $this->scheduler,
            [
                'source' => $sourceHandler,
                'ai_generation' => $aiHandler,
                'validation' => $validationHandler,
                'woocommerce' => $woocommerceHandler,
            ]
        );

        $this->tester = new PipelineTestService(
            $rows,
            [
                'source' => $sourceHandler,
                'ai_generation' => $aiHandler,
                'validation' => $validationHandler,
                'woocommerce' => $woocommerceHandler,
            ]
        );


        /*
         * ======================================================
         * 12. DISPATCHER
         * ======================================================
         */

        $this->dispatcher = new PipelineDispatcher(
            $imports,
            $rows,
            $this->jobs,
            $this->scheduler
        );


        /*
         * ======================================================
         * 13. RECOVERY
         * ======================================================
         */

        $this->recovery = new PipelineRecoveryService(
            $this->jobs,
            $this->scheduler
        );
    }

    /**
     * Initialise le pipeline.
     *
     * À appeler une seule fois depuis Plugin::boot().
     */
    public function register(): void
    {
        /*
         * Enregistre le hook Action Scheduler / WP-Cron.
         */
        $this->scheduler->register();

        /*
         * Programme la récupération des jobs bloqués.
         */
        $this->registerRecoveryHook();
    }

    /**
     * Lance le traitement d'un import.
     *
     * IMPORTANT :
     *
     * Cette méthode ne traite PAS les produits.
     *
     * Elle transforme uniquement les import_rows
     * existantes en jobs SOURCE.
     */
    public function processImport(
        int $importId,
        int $batchSize = 50
    ): int {
        return $this->dispatcher->dispatchImport(
            $importId,
            $batchSize
        );
    }

    /**
     * Exécute un job précis.
     */
    public function processJob(
        int $jobId
    ): void {
        $this->jobService->run(
            $jobId
        );
    }

    /**
     * Récupère les jobs bloqués.
     */
    public function recover(
        int $timeoutSeconds = 900
    ): int {
        return $this->recovery->recover(
            $timeoutSeconds
        );
    }

    /**
     * Retourne le repository des jobs.
     */
    public function jobs(): ProductJobRepository
    {
        return $this->jobs;
    }

    /**
     * Retourne le dispatcher.
     */
    public function dispatcher(): PipelineDispatcher
    {
        return $this->dispatcher;
    }

    /**
     * Retourne le service d'exécution des jobs.
     */
    public function jobService(): ProductJobService
    {
        return $this->jobService;
    }

    /**
     * Retourne le scheduler.
     */
    public function scheduler(): ProductJobScheduler
    {
        return $this->scheduler;
    }

    /**
     * Retourne le service de récupération.
     */
    public function recovery(): PipelineRecoveryService
    {
        return $this->recovery;
    }

    /**
     * Enregistre la récupération périodique des jobs bloqués.
     */
    private function registerRecoveryHook(): void
    {
        add_action(
            'my_ai_agent_pipeline_recovery',
            function (): void {
                $this->recovery->recover();
            }
        );

        /*
         * Toutes les 15 minutes.
         */
        if (
            !wp_next_scheduled(
                'my_ai_agent_pipeline_recovery'
            )
        ) {
            wp_schedule_event(
                time() + 300,
                'every_15_minutes',
                'my_ai_agent_pipeline_recovery'
            );
        }
    }

    public function tester(): PipelineTestService
    {
        return $this->tester;
    }
}