<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Repository\PromptRepository;

final class BuildPromptStep implements AgentStepInterface
{
    public function __construct(
        private readonly PromptRepository $promptRepository
    ) {
    }

    public function id(): string
    {
        return 'build_prompt';
    }

    public function label(): string
    {
        return __('Construction du prompt', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $promptId = (int) $context->get('prompt', 0);

        if ($promptId <= 0) {
            return StepResult::failed(
                __('Aucun prompt valide n\'a été sélectionné.', MY_AI_AGENT_DOMAIN)
            );
        }

        $prompt = $this->promptRepository->find($promptId);

        if ($prompt === null) {
            return StepResult::failed(
                __('Le prompt sélectionné est introuvable.', MY_AI_AGENT_DOMAIN)
            );
        }

        $content = $prompt->content;

        $variables = [
            '{{theme}}' => (string) $context->get('theme', ''),
            '{{language}}' => (string) $context->get('language', ''),
            '{{tone}}' => (string) $context->get('tone', ''),
        ];

        $promptInstructions = <<<'PROMPT'

## Image

Génère un prompt détaillé permettant de créer l'image principale de l'article.

Le prompt d'image doit :

- Être cohérent avec le thème.
- Décrire précisément la scène.
- Décrire le style visuel.
- Ne pas demander de texte dans l'image.
- Être utilisable directement avec un générateur d'images.
- Être rédigé dans la même langue que {{language}}, sauf si le générateur d'images nécessite une autre langue.
- Générer également un alt text pertinent pour l'image.

## JSON

Retourne UNIQUEMENT un objet JSON valide.

N'ajoute aucun texte avant ou après le JSON.

Utilise exactement cette structure :

{
    "postTitle": "Titre de l'article",
    "excerpt": "Résumé court et attractif de l'article",
    "content": "<p>Introduction...</p><h2>...</h2><p>...</p>",
    "seo": {
        "metaTitle": "Meta title",
        "metaDescription": "Meta description",
        "slug": "slug-de-l-article",
        "focusKeyword": "mot-clé principal",
        "keywords": [
            "mot-clé 1",
            "mot-clé 2",
            "mot-clé 3"
        ]
    },
    "image": {
        "prompt": "Prompt détaillé pour générer l'image principale",
        "alt": "Texte alternatif de l'image"
    },
    "faq": [
        {
            "question": "Question fréquente",
            "answer": "Réponse claire et concise"
        }
    ],
    "suggestedTags": [
        "tag 1",
        "tag 2",
        "tag 3"
    ],
    "suggestedCategories": [
        "catégorie 1"
    ]
}

## Règles JSON importantes

- Retourne un JSON strictement valide.
- Utilise des guillemets doubles pour toutes les clés et chaînes de caractères.
- N'utilise jamais de commentaires dans le JSON.
- Échappe correctement les guillemets présents dans les chaînes.
- Les retours à la ligne dans les chaînes doivent être correctement échappés ou évités.
- Le champ content doit contenir uniquement du HTML valide.
- Ne mets jamais de bloc Markdown ```json autour de la réponse.
- Ne retourne aucun texte en dehors de l'objet JSON.

PROMPT;

        $finalPrompt = $content . "\n\n" . $promptInstructions;

// Remplace toutes les variables, y compris celles présentes
// dans les instructions ajoutées ci-dessus.
        $finalPrompt = strtr($finalPrompt, $variables);

        if (trim($finalPrompt) === '') {
            return StepResult::failed(
                __('Le contenu du prompt est vide.', MY_AI_AGENT_DOMAIN)
            );
        }


        return StepResult::continue([
            'message' => __('Le prompt a été construit avec succès.', MY_AI_AGENT_DOMAIN),
            'prompt' => $finalPrompt,
        ]);
    }
}
