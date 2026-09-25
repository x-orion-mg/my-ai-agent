<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Provider\ProviderFactory;
use MyAIAgent\Repository\PromptRepository;
use MyAIAgent\Services\Settings;

final class legrandImportPage extends AbstractPage
{
    public function slug(): string
    {
        return 'legrand-import';
    }

    public function title(): string
    {
        return __('Import catalogue Legrand', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('Import Legrand', 'my-ai-agent');
    }

    public function render(): void
    {
        $fileUrl = plugins_url(
            'storage/exemple-product-legrand.csv',
            MY_AI_AGENT_FILE
        );


        $this->view('legrand-import', [
            'title' => $this->title(),
            'slug' => $this->slug(),
            'exempleCsv'=> $fileUrl,
        ]);
    }
}
