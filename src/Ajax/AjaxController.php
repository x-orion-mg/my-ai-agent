<?php
declare(strict_types=1);

namespace MyAIAgent\Ajax;

final class AjaxController extends AbstractController
{
    public function execute(): void
    {
        $this->guard();
        $this->success(
            [
                'message' => __('Agent exécutée.', MY_AI_AGENT_DOMAIN),
                'agent' =>$this->post('agent'),
                'data' => $_POST,
            ]
        );
    }
}