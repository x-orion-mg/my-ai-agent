<?php

declare(strict_types=1);

namespace MyAIAgent\Admin;

use MyAIAgent\Admin\Pages\ApiPage;
use MyAIAgent\Admin\Pages\DashboardPage;
use MyAIAgent\Admin\Pages\HistoryPage;
use MyAIAgent\Admin\Pages\LogsPage;
use MyAIAgent\Admin\Pages\PromptsPage;
use MyAIAgent\Admin\Pages\SettingsPage;
use MyAIAgent\Core\Container;

/**
 * Builds the "My AI Agent" admin menu and its sub-pages.
 */
final class AdminMenu
{
    private const CAPABILITY = 'manage_woocommerce';
    private const PARENT     = 'my-ai-agent';

    private Container $container;

    /** @var array<int, AbstractPage> */
    private array $pages;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->pages = [
            new DashboardPage($container),
            new SettingsPage($container),
            new PromptsPage($container),
            new ApiPage($container),
            new HistoryPage($container),
            new LogsPage($container),
        ];
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'buildMenu']);
        add_action('admin_init', [$this, 'handleFormPosts']);
    }

    public function buildMenu(): void
    {
        add_menu_page(
            __('My AI Agent', 'ai-product-studio'),
            __('My AI Agent', 'ai-product-studio'),
            self::CAPABILITY,
            self::PARENT,
            [$this->pages[0], 'render'],
            'dashicons-superhero',
            56
        );

        foreach ($this->pages as $index => $page) {
            $slug = $index === 0 ? self::PARENT : self::PARENT . '-' . $page->slug();

            add_submenu_page(
                self::PARENT,
                $page->title(),
                $page->menuTitle(),
                self::CAPABILITY,
                $slug,
                [$page, 'render']
            );
        }
    }

    /**
     * Handle non-AJAX settings form submissions (Configuration page).
     */
    public function handleFormPosts(): void
    {
        foreach ($this->pages as $page) {
            if ($page instanceof SettingsPage) {
                $page->maybeHandleSubmit();
            }
        }
    }
}
