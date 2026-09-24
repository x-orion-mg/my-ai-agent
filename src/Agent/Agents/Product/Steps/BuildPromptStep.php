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
        $infoProduct = 'reference : '. $context->get('reference', '');
        $infoProduct .= 'technical_name : '. $context->get('technical_name', '');
        $infoProduct .= 'family_name : '. $context->get('family_name', '');
        $infoProduct .= 'ean : '. $context->get('ean', '');
        $productOfficial = $context->get('product');
        $infoProduct .= 'lien du produit : '. $productOfficial->url;
        $infoProduct .= 'données techniques : '. $productOfficial->technicalData;
        $infoProduct .= 'Lien image du produit : '. $productOfficial->image;


        $content = $prompt->content;

        $variables = [
            '{{theme}}' => $infoProduct,
            '{{language}}' => (string) $context->get('language', ''),
            '{{tone}}' => (string) $context->get('tone', ''),
            '{{imageProduit}}' => $productOfficial->image
        ];

        $promptInstructions = <<<'PROMPT'
# 26. FORMAT DE SORTIE OBLIGATOIRE

Retourne **UNIQUEMENT** un objet JSON strictement valide.

N'ajoute aucun texte avant le JSON.

N'ajoute aucun texte après le JSON.

N'utilise aucun bloc Markdown.

N'utilise jamais :

```json
```

Utilise exactement cette structure :

{
"productName": "Nom du produit",
"shortDescription": "<p>Description courte...</p>",
"description": "<h2>Présentation du produit</h2><p>...</p>",
"caracteristiquesTechnique": "tableau HTML des caractéristiques techniques",
"sku": null,
"ean": null,
"brand": "Legrand",
"productType": null,
"category": "Niveau 1 > Niveau 2 > Niveau 3",
"categories": [
"Niveau 1",
"Niveau 2",
"Niveau 3"
],
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
"url" : "{{imageProduit}}",
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
"name": "Legrand"
}
},
"quality": {
"confidence": "high",
"needsReview": false,
"reviewReason": null
}
}

---

# 27. RÈGLES JSON

Le JSON doit être strictement valide.

Utilise des guillemets doubles pour toutes les clés et valeurs textuelles.

Échappe correctement les guillemets présents dans les textes.

N'utilise aucun commentaire.

N'utilise pas de Markdown dans les champs de description.

Les champs `shortDescription` et `description` doivent contenir uniquement du HTML valide.

Ne mets jamais de bloc ```json.

Ne retourne aucun texte en dehors du JSON.

Utilise `null` lorsqu'une donnée n'est pas disponible.

N'invente aucune information.

---

# 28. RÈGLE FINALE DE PRIORITÉ

En cas de conflit entre plusieurs informations, applique cet ordre de priorité :

1. Référence LEGRAND vérifiée.
2. Fiche produit officielle LEGRAND.
3. Documentation technique officielle LEGRAND.
4. Catalogue officiel LEGRAND.
5. Autres sources fiables uniquement pour aider à identifier le produit.
6. Données fournies en entrée.

Une information officielle vérifiée doit toujours être privilégiée par rapport à une supposition.

La classification doit toujours respecter **exactement** l'arborescence fournie.

La catégorie doit représenter la **fonction principale réelle du produit**.

Ne crée jamais de catégorie.

Ne modifie jamais une catégorie.

Ne transforme jamais une gamme en catégorie.

Ne transforme jamais une caractéristique technique en catégorie.

Ne transforme jamais une référence en catégorie.

La sortie finale doit être exclusivement le JSON demandé.

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
