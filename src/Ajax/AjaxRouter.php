<?php

declare(strict_types=1);

namespace MyAIAgent\Ajax;

use MyAIAgent\API\ApiKeyController;
use MyAIAgent\Core\Container;
use MyAIAgent\Prompt\PromptController;

/**
 * Maps AJAX actions to controller methods and registers them with WordPress.
 * Controllers are resolved lazily from the container.
 */
final class AjaxRouter
{
    private Container $container;

    /**
     * action => [controller-class, method]
     *
     * @var array<string, array{0: class-string, 1: string}>
     */
    private array $routes;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->routes = [
            'my_ai_agent_save_prompt'         => [PromptController::class, 'save'],
            'my_ai_agent_delete_prompt'       => [PromptController::class, 'delete'],
            'my_ai_agent_toggle_prompt'       => [PromptController::class, 'toggle'],
            'my_ai_agent_save_api_key'        => [ApiKeyController::class, 'save'],
            'my_ai_agent_delete_api_key'      => [ApiKeyController::class, 'delete'],
            'my_ai_agent_toggle_api_key'      => [ApiKeyController::class, 'toggle'],
            'my_ai_agent_create_execution'    => [AjaxController::class, 'create'],
            'my_ai_agent_run_execution'    => [AjaxController::class, 'run'],
            'my_ai_agent_resume_execution'    => [AjaxController::class, 'validate'],
        ];
    }

    public function register(): void
    {
        foreach ($this->routes as $action => [$class, $method]) {
            add_action('wp_ajax_' . $action, function () use ($class, $method): void {
                $controller = $this->container->get($class);
                $controller->{$method}();
            });
        }
    }
}
