<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Repository\PromptRepository;

final readonly class BuildPromptStep implements AgentStepInterface
{
    public function __construct(
        private PromptRepository $promptRepository
    ) {
    }

    public function id(): string
    {
        return 'build_prompt';
    }

    public function label(): string
    {
        return __('Construction du prompt Produit', MY_AI_AGENT_DOMAIN);
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
Format de sortie
Retourne UNIQUEMENT un objet JSON strictement valide.

N'ajoute aucun texte avant ou après le JSON.

Utilise exactement cette structure :

{
"productName": "Nom du produit",
"shortDescription": "<p>Description courte...</p>",
"description": "<h2>Présentation du produit</h2><p>...</p>",
"sku": null,
"ean": null,
"brand": null,
"productType": null,
"category": null,
"categories": [],
"tags": [],
"attributes": [
{
"name": "Nom de l'attribut",
"options": [
"Valeur"
]
}
],
"technicalSpecifications": [
{
"name": "Caractéristique",
"value": "Valeur"
}
],
"seo": {
"metaTitle": "Meta title",
"metaDescription": "Meta description",
"slug": "slug-produit",
"focusKeyword": "mot-clé principal",
"keywords": []
},
"image": {
"prompt": "Prompt détaillé pour générer l'image principale",
"alt": "Texte alternatif de l'image"
},
"faq": [
{
"question": "Question fréquente",
"answer": "Réponse"
}
],
"schema": {
"@context": "https://schema.org",
"@type": "Product",
"name": "Nom du produit",
"description": "Description du produit",
"sku": null,
"gtin": null,
"brand": {
"@type": "Brand",
"name": null
}
}
}

Règles JSON
Retourne uniquement le JSON.

Le JSON doit être strictement valide.

Utilise des guillemets doubles pour toutes les clés et valeurs textuelles.

N'utilise aucun commentaire.

Échappe correctement les guillemets présents dans les textes.

N'utilise pas de Markdown dans les champs de description.

Les champs "shortDescription" et "description" doivent contenir uniquement du HTML valide.

Ne mets jamais de bloc ```json.

Ne retourne aucun texte en dehors du JSON.

Utilise null lorsqu'une donnée n'est pas disponible.

N'invente aucune information.

Respecte strictement la langue {{langue}}.

Respecte strictement le ton {{ton}}.
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
