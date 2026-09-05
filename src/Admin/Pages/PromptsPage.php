<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Repository\PromptRepository;

final class PromptsPage extends AbstractPage
{
    public function slug(): string
    {
        return 'prompts';
    }

    public function title(): string
    {
        return __('Prompts — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Prompts', 'my-ai-agent');
    }

    public function render(): void
    {
        /** @var PromptRepository $repository */
        $repository = $this->container->get(PromptRepository::class);

        $this->view('prompts', [
            'prompts' => $repository->all(),
        ]);
    }
}
