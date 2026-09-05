<?php

declare(strict_types=1);

namespace MyAIAgent\Ajax;

use MyAIAgent\Repository\ApiKeyRepository;

/**
 * CRUD AJAX endpoints for AI provider API keys.
 */
final class ApiKeyController extends AbstractController
{
    private ApiKeyRepository $repository;

    public function __construct(ApiKeyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function save(): void
    {
        $this->guard();

        $provider = $this->post('provider');
        
        $data = [
            'provider'  => $provider,
            'label'     => $this->post('label'),
            'api_key'   => isset($_POST['api_key']) ? trim(wp_unslash((string) $_POST['api_key'])) : '',
            'model'     => $this->post('model'),
            'endpoint'  => esc_url_raw(wp_unslash((string) ($_POST['endpoint'] ?? ''))),
            'priority'  => $this->postInt('priority', 10),
            'is_active' => $this->postInt('is_active') === 1,
        ];

        $id = $this->postInt('id');

        if ($id > 0) {
            // Do not overwrite the stored key with an empty value on edit.
            if ($data['api_key'] === '') {
                unset($data['api_key']);
            }
            $this->repository->update($id, $data);
        } else {
            $id = $this->repository->create($data);
        }

        $this->success([
            'id'      => $id,
            'message' => __('Clé API enregistrée.', MY_AI_AGENT_DOMAIN),
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

        $this->success(['message' => __('Clé API supprimée.', MY_AI_AGENT_DOMAIN)]);
    }

    public function toggle(): void
    {
        $this->guard();

        $id  = $this->postInt('id');
        $key = $this->repository->find($id);

        if ($key === null) {
            $this->fail(__('Clé introuvable.', MY_AI_AGENT_DOMAIN));
        }

        $this->repository->update($id, ['is_active' => ! $key->isActive]);

        $this->success(['is_active' => ! $key->isActive]);
    }
}
