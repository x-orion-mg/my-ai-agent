<?php

declare(strict_types=1);

namespace MyAIAgent\AI;

use MyAILib\MyAI;
use MyAILib\Response\AIResponse;

final class AIService
{
    /**
     * Create an AI client.
     *
     * @param array<string, mixed> $config
     */
    public function create(array $config): MyAI
    {
        return MyAI::create($config);
    }

    /**
     * Create an AI client using a session.
     *
     * @param array<string, mixed> $config
     */
    public function session(
        string $sessionId,
        array $config
    ): MyAI {
        return $this->create($config)
            ->session($sessionId);
    }

    /**
     * Send a prompt to the AI.
     *
     * @param array<string, mixed> $config
     */
    public function ask(
        string $prompt,
        array $config
    ): AIResult {
        $response = $this->create($config)->ask($prompt);

        return $this->mapResponse($response);
    }

    /**
     * Send a prompt using an AI session.
     *
     * @param array<string, mixed> $config
     */
    public function askInSession(
        string $sessionId,
        string $systemPrompt,
        string $prompt,
        array $config
    ): AIResult {
        $ai = $this->create($config)
            ->session($sessionId)
            ->setSystemPrompt($systemPrompt);

        $response = $ai->ask($prompt);

        return $this->mapResponse($response);
    }

    /**
     * Convert MyAILib response to our internal result.
     */
    private function mapResponse(AIResponse $response): AIResult
    {
        return new AIResult(
            text: $response->text(),
            provider: $response->provider(),
            model: $response->model(),
            finishReason: $response->finishReason(),
            usage: $response->usage(),
            metadata: $response->metadata(),
        );
    }
}
