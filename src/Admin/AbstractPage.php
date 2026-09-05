<?php

declare(strict_types=1);

namespace MyAIAgent\Admin;

use MyAIAgent\Core\Container;

/**
 * Base class for admin pages. Pages prepare their data (controller role) and
 * delegate rendering to a template file, keeping business logic out of views.
 */
abstract class AbstractPage
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function slug(): string;

    abstract public function title(): string;

    abstract public function menuTitle(): string;

    /**
     * Render the page. Prepares data then includes the matching template.
     */
    abstract public function render(): void;

    /**
     * Include a template with the provided data extracted as variables.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $template, array $data = []): void
    {
        $file = MY_AI_AGENT_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html("Template introuvable : {$template}"));

            return;
        }

        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract($data, EXTR_SKIP);

        require $file;
    }
}
