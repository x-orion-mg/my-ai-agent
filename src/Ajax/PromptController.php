<?php

declare(strict_types=1);

namespace MyAiAgent\Ajax;

use MyAiAgent\Repository\PromptRepository;

/**
 * CRUD AJAX endpoints for prompts.
 */
final class PromptController extends AbstractController
{
    private PromptRepository $repository;

    public function __construct(PromptRepository $repository)
    {
        $this->repository = $repository;
    }

    public function save(): void
    {
        $this->guard();

        // Prompt content may legitimately contain markup/braces, keep it raw but unslashed.
        $content = isset($_POST['content']) ? wp_kses_post(wp_unslash((string) $_POST['content'])) : '';

        $data = [
            'name'        => $this->post('name'),
            'description' => $this->postTextarea('description'),
            'content'     => $content,
            'is_active'   => $this->postInt('is_active') === 1,
        ];

        if ($data['name'] === '') {
            $this->fail(__('Le nom du prompt est obligatoire.', MY_AI_AGENT_DOMAIN));
        }

        $id = $this->postInt('id');

        if ($id > 0) {
            $this->repository->update($id, $data);
        } else {
            $id = $this->repository->create($data);
        }

        $this->success([
            'id'      => $id,
            'message' => __('Prompt enregistré.', MY_AI_AGENT_DOMAIN),
        ]);
    }

    public function delete(): void
    {
        $this->guard();

        $id = $this->postInt('id');
        if ($id <= 0) {
            $this->fail(__('Identifiant invalide.', MY_AI_AGENT_DOMAIN));
        }

        $this->repository->delete($id);

        $this->success(['message' => __('Prompt supprimé.', MY_AI_AGENT_DOMAIN)]);
    }

    public function toggle(): void
    {
        $this->guard();

        $id     = $this->postInt('id');
        $prompt = $this->repository->find($id);

        if ($prompt === null) {
            $this->fail(__('Prompt introuvable.', MY_AI_AGENT_DOMAIN));
        }

        $this->repository->update($id, ['is_active' => ! $prompt->isActive]);

        $this->success(['is_active' => ! $prompt->isActive]);
    }
}
