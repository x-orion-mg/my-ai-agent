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
        $blog = $context->get('blog');

        if (! is_array($blog)) {
            return StepResult::failed(
                'Les données du blog sont manquantes ou invalides.'
            );
        }

        $postTitle = $blog['postTitle'] ?? null;
        $excerpt   = $blog['excerpt'] ?? '';
        $content   = $blog['content'] ?? '';

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

        if (! is_string($excerpt)) {
            $excerpt = '';
        }

        try {
            $postId = $this->blogPostService->create([
                'postTitle' => $postTitle,
                'excerpt'   => $excerpt,
                'content'   => $content,
            ]);
            $editUrl = get_edit_post_link($postId);
            $message = sprintf(
                __('L’article a été créé avec succès. <a href="%s" target="_blank">Modifier l’article</a>', MY_AI_AGENT_DOMAIN),
                esc_url($editUrl)
            );
            return StepResult::continue([
                'message' => $message,
            ]);

        } catch (Throwable $e) {
            return StepResult::failed(
                $e->getMessage()
            );
        }
    }

}
