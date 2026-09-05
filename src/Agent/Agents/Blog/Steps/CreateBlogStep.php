<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use MyAIAgent\Services\Blog\BlogPostService;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\StepResult;
use Throwable;

final class CreateBlogStep implements AgentStepInterface
{
    public function __construct(
        private readonly BlogPostService $blogPostService
    ) {
    }

    public function id(): string
    {
        return 'create-blog';
    }

    public function label(): string
    {
        return 'Création de l’article';
    }

    public function execute(AgentContext $context): StepResult
    {
        $postTitle = $context->get('postTitle');
        $excerpt   = $context->get('excerpt');
        $content   = $context->get('content');

        if (! is_string($postTitle) || trim($postTitle) === '') {
            return StepResult::failed(
                'Le titre de l’article est manquant.'
            );
        }

        if (! is_string($content) || trim($content) === '') {
            return StepResult::failed(
                'Le contenu de l’article est manquant.'
            );
        }

        try {
            $postId = $this->blogPostService->create([
                'postTitle' => $postTitle,
                'excerpt'   => is_string($excerpt) ? $excerpt : '',
                'content'   => $content,
            ]);

            return StepResult::continue([
                'post_id' => $postId,
            ]);
        } catch (Throwable $e) {
            return StepResult::failed(
                $e->getMessage()
            );
        }
    }
}
