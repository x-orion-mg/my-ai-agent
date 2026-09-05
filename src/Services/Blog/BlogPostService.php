<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Blog;

use RuntimeException;

final class BlogPostService
{
    /**
     * Create a WordPress blog post.
     *
     * @param array<string, mixed> $data
     *
     * @return int Post ID.
     *
     * @throws RuntimeException When the post cannot be created.
     */
    public function create(array $data): int
    {
        $title = isset($data['postTitle'])
            ? trim((string) $data['postTitle'])
            : '';

        $content = isset($data['content'])
            ? (string) $data['content']
            : '';

        $excerpt = isset($data['excerpt'])
            ? (string) $data['excerpt']
            : '';

        if ($title === '') {
            throw new RuntimeException(
                'Le titre de l’article est manquant.'
            );
        }

        if ($content === '') {
            throw new RuntimeException(
                'Le contenu de l’article est manquant.'
            );
        }

        $postData = [
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_excerpt' => sanitize_textarea_field($excerpt),
            'post_status'  => 'draft',
            'post_type'    => 'post',
        ];

        $postId = wp_insert_post(
            $postData,
            true
        );

        if (is_wp_error($postId)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de créer l’article : %s',
                    $postId->get_error_message()
                )
            );
        }

        $postId = (int) $postId;

        if ($postId <= 0) {
            throw new RuntimeException(
                'WordPress n’a pas retourné un identifiant d’article valide.'
            );
        }

        return $postId;
    }
}
