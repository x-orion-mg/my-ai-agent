<?php


declare(strict_types=1);

namespace MyAIAgent\Agent\Response;

use JsonException;

final class AiResponseParser
{
    /**
     * Parse une réponse brute provenant de l'IA.
     *
     * @return array<string, mixed>
     *
     * @throws AiResponseParseException
     */
    public function parse(string $response): array
    {
        $json = $this->clean($response);

        try {
            /** @var mixed $decoded */
            $decoded = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new AiResponseParseException(
                sprintf(
                    'La réponse de l\'IA contient un JSON invalide : %s',
                    $exception->getMessage()
                ),
                previous: $exception
            );
        }

        if (!is_array($decoded)) {
            throw new AiResponseParseException(
                'La réponse de l\'IA doit être un objet JSON.'
            );
        }

        return $decoded;
    }

    public function clean(string $response): string
    {
        $response = trim($response);

        if ($response === '') {
            throw new AiResponseParseException(
                'La réponse de l\'IA est vide ou manquante.'
            );
        }

        /*
         * Certains modèles retournent :
         *
         * ```json
         * {...}
         * ```
         *
         * Même si le prompt interdit Markdown, on accepte ce format
         * pour rendre le pipeline plus robuste.
         */
        if (preg_match(
            '/^```(?:json)?\s*(.*?)\s*```$/is',
            $response,
            $matches
        )) {
            $response = trim($matches[1]);
        }

        return $response;
    }
}
