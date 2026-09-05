<?php

declare(strict_types=1);

namespace MyAIAgent\Core;


use MyAIAgent\Admin\AdminMenu;
use MyAIAgent\Agent\AgentManager;
use MyAIAgent\Agent\Agents\Blog\BlogAgent;
use MyAIAgent\Agent\Agents\Blog\Steps\AskAiStep;
use MyAIAgent\Agent\Agents\Blog\Steps\BuildPromptStep;
use MyAIAgent\Agent\Agents\Blog\Steps\CreateBlogStep;
use MyAIAgent\Agent\Agents\Blog\Steps\HumanValidationStep;
use MyAIAgent\Agent\Agents\Blog\Steps\ProcessAiResponseStep;
use MyAIAgent\Agent\Agents\Blog\Steps\ValidateInputStep;
use MyAIAgent\AI\AIService;
use MyAIAgent\Ajax\AjaxController;
use MyAIAgent\Ajax\AjaxRouter;
use MyAIAgent\API\ApiKeyController;
use MyAIAgent\API\ApiKeyRotator;
use MyAIAgent\Execution\ExecutionManager;
use MyAIAgent\Logger\Logger;
use MyAIAgent\Prompt\PromptController;
use MyAIAgent\Provider\ProviderFactory;
use MyAIAgent\Repository\ApiKeyRepository;
use MyAIAgent\Repository\ExecutionRepository;
use MyAIAgent\Repository\HistoryRepository;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Blog\BlogPostService;
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
            AIService::class,
            static fn (): AIService => new AIService()
        );

        $this->container->singleton(
            AgentManager::class,
            static fn (): AgentManager => new AgentManager()
        );

        $this->container->singleton(
            ExecutionRepository::class,
            static fn (): ExecutionRepository => new ExecutionRepository()
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
            static fn (): PromptRepository => new PromptRepository()
        );
        $this->container->singleton(
            ApiKeyRepository::class,
            static fn (): ApiKeyRepository => new ApiKeyRepository()
        );
        $this->container->singleton(
            HistoryRepository::class,
            static fn (): HistoryRepository => new HistoryRepository()
        );

        $this->container->singleton(
            Settings::class,
            static fn (): Settings => new Settings()
        );

        $this->container->singleton(
            Logger::class,
            static fn (Container $c): Logger => new Logger($c->get(Settings::class))
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
            static fn (Container $c): AjaxRouter => new AjaxRouter($c)
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
            static fn (Container $c): ApiKeyRotator => new ApiKeyRotator(
                $c->get(ApiKeyRepository::class),
                $c->get(Settings::class)
            )
        );
        $this->container->singleton(
            AIService::class,
            static fn (Container $c): AIService => new AIService(
                $c->get(ProviderFactory::class),
                $c->get(ApiKeyRotator::class)
            )
        );


        $this->container->singleton(
            ValidateInputStep::class,
            static fn (Container $c): ValidateInputStep => new ValidateInputStep()
        );
        $this->container->singleton(
            BuildPromptStep::class,
            static fn (Container $c): BuildPromptStep => new BuildPromptStep($c->get(PromptRepository::class))
        );
        $this->container->singleton(
            AskAiStep::class,
            static fn (Container $c): AskAiStep => new AskAiStep()
        );
        $this->container->singleton(
            ProcessAiResponseStep::class,
            static fn (Container $c): ProcessAiResponseStep => new ProcessAiResponseStep()
        );
        $this->container->singleton(
            HumanValidationStep::class,
            static fn (Container $c): HumanValidationStep => new HumanValidationStep()
        );
        $this->container->singleton(
            BlogPostService::class,
            static fn (): BlogPostService => new BlogPostService()
        );
        $this->container->singleton(
            CreateBlogStep::class,
            static fn (Container $c): CreateBlogStep => new CreateBlogStep($c->get(BlogPostService::class))
        );


        $this->container->singleton(
            BlogAgent::class,
            static fn (Container $c): BlogAgent => new BlogAgent(
                $c->get(ValidateInputStep::class),
                $c->get(BuildPromptStep::class),
                $c->get(AskAiStep::class),
                $c->get(ProcessAiResponseStep::class),
                $c->get(HumanValidationStep::class),
                $c->get(CreateBlogStep::class)
            )
        );

        //admin
        $this->container->singleton(
            AdminMenu::class,
            static fn (Container $c): AdminMenu => new AdminMenu($c)
        );
        $this->container->singleton(
            Assets::class,
            static fn (Container $c): Assets => new Assets()
        );

    }

    private function registerAgents(): void
    {
        /** @var AgentManager $agentManager */
        $agentManager = $this->container->get(AgentManager::class);

        $agentManager->register(
            $this->container->get(BlogAgent::class)
        );
    }

    private function testExecution(): void
    {
        /** @var ExecutionManager $manager */
        $manager = $this->container->get(
            ExecutionManager::class
        );

        $execution = $manager->create(
            agentId: 'test',
            input: [
                'message' => 'Réponds uniquement par : Execution fonctionne.',
            ]
        );

        error_log(
            'EXECUTION CREATED: ' . $execution->id()
        );

        $execution = $manager->run($execution);

        error_log(
            'EXECUTION STATUS: ' . $execution->status()
        );

        error_log(
            'EXECUTION DATA: ' . print_r(
                $execution->data(),
                true
            )
        );

        error_log(
            'EXECUTION ERROR: ' . ($execution->error() ?? 'none')
        );
    }

}
