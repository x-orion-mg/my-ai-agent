<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\Response\AiResponseParseException;
use MyAIAgent\Agent\Response\AiResponseParser;
use MyAIAgent\Agent\Response\AiResponseValidationException;
use MyAIAgent\Agent\Response\AiResponseValidatorInterface;
use MyAIAgent\Agent\StepResult;

final class ProcessAiResponseStep implements AgentStepInterface
{
    public function __construct(
        private readonly AiResponseParser $parser,
        private readonly AiResponseValidatorInterface $validator,
    ) {
    }

    public function id(): string
    {
        return 'parse-ai-response';
    }

    public function label(): string
    {
        return 'Analyse de la réponse IA';
    }

    public function execute(AgentContext $context): StepResult
    {
        $response = $context->get('ai_response');

        if (! is_string($response)) {
            return StepResult::failed(
                'La réponse de l\'IA est vide ou manquante.'
            );
        }

        try {
            $data = $this->parser->parse($response);

            $this->validator->validate($data);
        } catch (
        AiResponseParseException |
        AiResponseValidationException $exception
        ) {
            return StepResult::failed(
                $exception->getMessage()
            );
        }

        $message = sprintf(
            __(
                'La réponse de l\'IA a été traitée avec succès. Veuillez valider le contenu généré par l’IA avant de créer l’article.\n\n<pre>%s</pre>',
                MY_AI_AGENT_DOMAIN
            ),
            esc_html(
                wp_json_encode(
                    $data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                )
            )
        );

        return StepResult::continue([
            'message' => $message,
            'ai_response_raw' => $response,
            'blog' => $data,
        ]);
    }
}
