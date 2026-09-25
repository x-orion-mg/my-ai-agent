<?php

declare(strict_types=1);

namespace MyAIAgent\Core;


use MyAIAgent\Admin\AdminMenu;
use MyAIAgent\Agent\AgentManager;
use MyAIAgent\Agent\Agents\Blog\BlogAgent;
use MyAIAgent\Agent\Agents\Blog\Response\BlogResponseValidator;
use MyAIAgent\Agent\Agents\Blog\Steps\BuildPromptStep;
use MyAIAgent\Agent\Agents\Blog\Steps\CreateBlogStep;
use MyAIAgent\Agent\Agents\Blog\Steps\HumanValidationStep;
use MyAIAgent\Agent\Agents\Legrand\LegrandAgent;
use MyAIAgent\Agent\Agents\Legrand\Response\LegrandCsvValidationResult;
use MyAIAgent\Agent\Agents\Legrand\Steps\ValidateCsvInputStep;
use MyAIAgent\Agent\Agents\Product\ProductAgent;
use MyAIAgent\Agent\Agents\Product\Response\ProductResponseValidator;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep as ProductBuildPromptStep;
use MyAIAgent\Agent\Agents\Product\Steps\CreateProductStep;
use MyAIAgent\Agent\Agents\Product\Steps\GetProductOfficialStep;
use MyAIAgent\Agent\Response\AiResponseParser;
use MyAIAgent\Agent\Steps\AskAiStep;
use MyAIAgent\Agent\Steps\ProcessAiResponseStep;
use MyAIAgent\Agent\Steps\ValidateInputStep;
use MyAIAgent\AI\AIService;
use MyAIAgent\Ajax\AjaxController;
use MyAIAgent\Ajax\AjaxRouter;
use MyAIAgent\API\ApiKeyController;
use MyAIAgent\API\ApiKeyRotator;
use MyAIAgent\Execution\ExecutionManager;
use MyAIAgent\Execution\StepExecutionResult;
use MyAIAgent\Logger\Logger;
use MyAIAgent\Prompt\PromptController;
use MyAIAgent\Provider\ProviderFactory;
use MyAIAgent\Repository\ApiKeyRepository;
use MyAIAgent\Repository\ExecutionRepository;
use MyAIAgent\Repository\HistoryRepository;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Blog\BlogPostService;
use MyAIAgent\Services\Product\ProductService;
use MyAIAgent\Services\Settings;

final class Plugin
{
    private bool $booted = false;
    private static ?self $instance = null;

    private Container $container;

    private function __construct()
    {
        $this->container = new Container();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        load_plugin_textdomain(MY_AI_AGENT_DOMAIN, false, dirname(MY_AI_AGENT_DIR) . '/languages');

        $this->registerServices();
        $this->registerAgents();

        add_action('admin_notices', [$this, 'maybeWooCommerceNotice']);

        if (is_admin()) {
            /** @var AdminMenu $menu */
            $menu = $this->container->get(AdminMenu::class);
            $menu->register();

            /** @var Assets $assets */
            $assets = $this->container->get(Assets::class);
            $assets->register();
        }
        // AJAX endpoints (available in admin context).
        /** @var AjaxRouter $router */
        $router = $this->container->get(AjaxRouter::class);
        $router->register();
    }

    public function maybeWooCommerceNotice(): void
    {
        if (class_exists('WooCommerce')) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__('My AI Agent nécessite WooCommerce pour créer des produits. Veuillez installer et activer WooCommerce.', 'ai-product-studio')
        );
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function registerServices(): void
    {
        $this->container->singleton(
            AgentManager::class,
            static fn(): AgentManager => new AgentManager()
        );

        $this->container->singleton(
            ExecutionRepository::class,
            static fn(): ExecutionRepository => new ExecutionRepository()
        );

        $this->container->singleton(
            ExecutionManager::class,
            function (Container $container): ExecutionManager {
                return new ExecutionManager(
                    $container->get(AgentManager::class),
                    $container->get(AIService::class),
                    $container->get(ExecutionRepository::class),
                );
            }
        );


        $this->container->singleton(
            PromptRepository::class,
            static fn(): PromptRepository => new PromptRepository()
        );
        $this->container->singleton(
            ApiKeyRepository::class,
            static fn(): ApiKeyRepository => new ApiKeyRepository()
        );
        $this->container->singleton(
            HistoryRepository::class,
            static fn(): HistoryRepository => new HistoryRepository()
        );

        $this->container->singleton(
            Settings::class,
            static fn(): Settings => new Settings()
        );

        $this->container->singleton(
            Logger::class,
            static fn(Container $c): Logger => new Logger($c->get(Settings::class))
        );

        $this->container->singleton(
            ProviderFactory::class,
            static fn(Container $c): ProviderFactory => new ProviderFactory(
                $c->get(Logger::class),
                $c->get(Settings::class)
            )
        );

        $this->container->singleton(
            AjaxRouter::class,
            static fn(Container $c): AjaxRouter => new AjaxRouter($c)
        );
        $this->container->singleton(
            PromptController::class,
            static fn(Container $c): PromptController => new PromptController($c->get(PromptRepository::class))
        );
        $this->container->singleton(
            ApiKeyController::class,
            static fn(Container $c): ApiKeyController => new ApiKeyController($c->get(ApiKeyRepository::class))
        );
        $this->container->singleton(
            AjaxController::class,
            static fn(Container $c): AjaxController => new AjaxController($c->get(ExecutionManager::class))
        );

        $this->container->singleton(
            ApiKeyRotator::class,
            static fn(Container $c): ApiKeyRotator => new ApiKeyRotator(
                $c->get(ApiKeyRepository::class),
                $c->get(Settings::class)
            )
        );
        $this->container->singleton(
            AIService::class,
            static fn(Container $c): AIService => new AIService(
                $c->get(ProviderFactory::class),
                $c->get(ApiKeyRotator::class)
            )
        );

        $this->container->singleton(
            StepExecutionResult::class,
            static fn(): StepExecutionResult => new StepExecutionResult()
        );
        $this->container->singleton(
            ValidateInputStep::class,
            static fn(Container $c): ValidateInputStep => new ValidateInputStep()
        );
        $this->container->singleton(
            BuildPromptStep::class,
            static fn(Container $c): BuildPromptStep => new BuildPromptStep($c->get(PromptRepository::class))
        );
        $this->container->singleton(
            AskAiStep::class,
            static fn(Container $c): AskAiStep => new AskAiStep()
        );

        $this->container->singleton(
            AiResponseParser::class,
            static fn(Container $c): AiResponseParser => new AiResponseParser()
        );
        $this->container->singleton(
            BlogResponseValidator::class,
            static fn(Container $c): BlogResponseValidator => new BlogResponseValidator()
        );

        $this->container->singleton(
            'blog.process_ai_response_step',
            static fn(Container $c): ProcessAiResponseStep => new ProcessAiResponseStep(
                $c->get(AiResponseParser::class),
                $c->get(BlogResponseValidator::class),
            )
        );
        $this->container->singleton(
            HumanValidationStep::class,
            static fn(Container $c): HumanValidationStep => new HumanValidationStep()
        );
        $this->container->singleton(
            BlogPostService::class,
            static fn(): BlogPostService => new BlogPostService()
        );
        $this->container->singleton(
            CreateBlogStep::class,
            static fn(Container $c): CreateBlogStep => new CreateBlogStep($c->get(BlogPostService::class))
        );


        $this->container->singleton(
            BlogAgent::class,
            static fn(Container $c): BlogAgent => new BlogAgent(
                $c->get(ValidateInputStep::class),
                $c->get(BuildPromptStep::class),
                $c->get(AskAiStep::class),
                $c->get('blog.process_ai_response_step'),
                $c->get(HumanValidationStep::class),
                $c->get(CreateBlogStep::class)
            )
        );

        $this->container->singleton(
            ProductBuildPromptStep::class,
            static fn(Container $c): ProductBuildPromptStep => new ProductBuildPromptStep($c->get(PromptRepository::class))
        );

        $this->container->singleton(
            ProductResponseValidator::class,
            static fn(Container $c): ProductResponseValidator => new ProductResponseValidator()
        );

        $this->container->singleton(
            ProductService::class,
            static fn(Container $c): ProductService => new ProductService()
        );
        $this->container->singleton(
            CreateProductStep::class,
            static fn(Container $c): CreateProductStep => new CreateProductStep($c->get(ProductService::class))
        );

        $this->container->singleton(
            'product.process_ai_response_step',
            static fn(Container $c): ProcessAiResponseStep => new ProcessAiResponseStep(
                $c->get(AiResponseParser::class),
                $c->get(ProductResponseValidator::class),
            )
        );
        $this->container->singleton(
            GetProductOfficialStep::class,
            static fn(Container $c): GetProductOfficialStep => new GetProductOfficialStep()
        );

        $this->container->singleton(
            ProductAgent::class,
            static fn (Container $c): ProductAgent => new ProductAgent(
                $c->get(GetProductOfficialStep::class),
                $c->get(ProductBuildPromptStep::class),
                $c->get(AskAiStep::class),
                $c->get('product.process_ai_response_step'),
                $c->get(CreateProductStep::class)
            )
        );

        // Agent Legrand
        $this->container->singleton(
            LegrandCsvValidationResult::class,
            static fn(Container $c) : LegrandCsvValidationResult => new LegrandCsvValidationResult()
        );
        $this->container->singleton(ValidateCsvInputStep::class,
            static fn(Container $c): ValidateCsvInputStep =>  new ValidateCsvInputStep()
        );

        $this->container->singleton(
            LegrandAgent::class,
            static fn(Container $c): LegrandAgent => new LegrandAgent(
                $c->get(ValidateCsvInputStep::class),
            )
        );

        //admin
        $this->container->singleton(
            AdminMenu::class,
            static fn(Container $c): AdminMenu => new AdminMenu($c)
        );
        $this->container->singleton(
            Assets::class,
            static fn(Container $c): Assets => new Assets()
        );

    }

    private function registerAgents(): void
    {
        /** @var AgentManager $agentManager */
        $agentManager = $this->container->get(AgentManager::class);

        $agentManager->register(
            $this->container->get(BlogAgent::class),
        );
        $agentManager->register(
            $this->container->get(LegrandAgent::class)
        );
    }

}
