<?php

declare(strict_types=1);

namespace MyAIAgent\Admin\Pages;

use MyAIAgent\Admin\AbstractPage;
use MyAIAgent\Repository\ApiKeyRepository;
use MyAIAgent\Provider\ProviderFactory;

final class ApiPage extends AbstractPage
{
    public function slug(): string
    {
        return 'api';
    }

    public function title(): string
    {
        return __('API — My AI Agent', 'my-ai-agent');
    }

    public function menuTitle(): string
    {
        return __('API', 'my-ai-agent');
    }

    public function render(): void
    {
        /** @var ApiKeyRepository $repository */
        $repository = $this->container->get(ApiKeyRepository::class);
        /** @var ProviderFactory $factory */
        $factory = $this->container->get(ProviderFactory::class);

        $this->view('api', [
            'keys'      => $repository->all(),
            'providers' => $factory->available(),
        ]);
    }
}
