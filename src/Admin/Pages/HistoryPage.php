<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Repository\HistoryRepository;

final class HistoryPage extends AbstractPage
{
    private const PER_PAGE = 20;

    public function slug(): string
    {
        return 'history';
    }

    public function title(): string
    {
        return __('Historique — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Historique', 'my-ai-agent');
    }

    public function render(): void
    {
        /** @var HistoryRepository $repository */
        $repository = $this->container->get(HistoryRepository::class);

        $page  = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $total = $repository->count();

        $this->view('history', [
            'entries'  => $repository->paginate($page, self::PER_PAGE),
            'page'     => $page,
            'perPage'  => self::PER_PAGE,
            'total'    => $total,
            'pages'    => (int) ceil($total / self::PER_PAGE),
        ]);
    }
}
