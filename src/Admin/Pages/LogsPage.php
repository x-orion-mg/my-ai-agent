<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Logger\Logger;

final class LogsPage extends AbstractPage
{
    public function slug(): string
    {
        return 'logs';
    }

    public function title(): string
    {
        return __('Logs — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Logs', 'my-ai-agent');
    }

    public function render(): void
    {
        /** @var Logger $logger */
        $logger = $this->container->get(Logger::class);

        // Handle "clear logs" action.
        if (isset($_POST['my_ai_agent_clear_logs']) && check_admin_referer('my_ai_agent_clear_logs', 'my_ai_agent_logs_nonce')) {
            $logger->clear();
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Logs vidés.', 'my-ai-agent') . '</p></div>';
        }

        $this->view('logs', [
            'lines' => $logger->tail(300),
        ]);
    }
}
