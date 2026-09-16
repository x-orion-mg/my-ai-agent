<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Steps;

use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\StepResult;

final class AskAiStep implements AgentStepInterface
{

    public function id(): string
    {
        return 'ask_ai';
    }

    public function label(): string
    {
        return __('Demande à l\'IA', MY_AI_AGENT_DOMAIN);
    }

    public function execute(AgentContext $context): StepResult
    {
        $prompt = $context->get('prompt');

        if (!is_string($prompt) || trim($prompt) === '') {
            return StepResult::failed(
                'Le prompt est manquant.'
            );
        }

        $provider = $context->get('provider');

        if (!is_string($provider) || trim($provider) === '') {
            return StepResult::failed(
                'Aucun fournisseur IA n’a été sélectionné.'
            );
        }
        /*
                 * Mode d'exécution :
                 *
                 * test = utilise une réponse JSON locale
                 * ai   = utilise réellement le fournisseur IA
                 */
        $mode = $context->get('mode');
        $mode = 'test';
        if (! is_string($mode) || trim($mode) === '') {
            $mode = 'ai';
        }
        try {
            if ($mode === 'test') {
               return $this->getTestResult();
            } else {
                $result = $context->ai()->ask(
                    $provider,
                    $prompt,
                    [
                        'session_id' => $context->executionId(),
                    ]
                );


                return StepResult::continue([
                    'message' => __('Réponse de l’IA reçue avec succès.', MY_AI_AGENT_DOMAIN),
                    'ai_response' => $result->text(),
                    'ai_provider' => $result->provider(),
                    'ai_model' => $result->model(),
                    'ai_finish_reason' => $result->finishReason(),
                    'ai_usage' => $result->usage(),
                ]);
            }

        } catch (\Throwable $exception) {
            return StepResult::failed(
                $exception->getMessage()
            );
        }
    }

    private function getTestResult(): StepResult
    {
        $test_result = <<<'JSON'
{
  "productName": "Disjoncteur modulaire RX3 1P B10 6000A BIC - Réf. 419134",
  "shortDescription": "Disjoncteur modulaire RX3 - Référence 419134. Unipolaire, calibre B10, capacité de coupure 6000A. Protège votre installation électrique dans les tableaux de distribution.",
  "description": "Présentation du produit\nLe disjoncteur modulaire RX3 est un appareil de protection électrique conçu pour les tableaux de distribution. De référence 419134, il assure la protection de votre installation contre les surcharges et les courts-circuits.\n\nCaractéristiques techniques\nCaractéristique\tValeur\nRéférence\t419134\nEAN\t3414970366665\nGamme\tRX3\nType\tDisjoncteur modulaire\nNombre de pôles\t1P (unipolaire)\nCalibre\tB10\nCapacité de coupure\t6000A\nType / designation\tBIC\n\nÀ quoi sert ce produit ?\nCe disjoncteur modulaire de la gamme RX3 est destiné à être installé dans un tableau électrique afin de protéger les circuits contre les surcharges et les défauts de courant. Sa capacité de coupure de 6000A garantit une protection adaptée aux installations résidentielles et tertiaires.\n\nAvantages\nMontage modulaire standard compatible avec les tableaux électriques courants.\nCalibre B10 adapté aux circuits d'éclairage et des petites puissances.\nHaute capacité de coupure pour une protection fiable.\n\nInformations importantes\nRéférence fabricant : 419134. EAN : 3414970366665. Le disjoncteur RX3 1P B10 6000A BIC fait partie de la gamme RX3, destinée aux appareils de protection dans les catégories Tableaux & Protection électrique et Disjoncteurs modulaires.\n\nDécouvrez notre gamme de disjoncteurs modulaires RX3\nConsultez l'ensemble de notre offre de disjoncteurs et d'appareils de protection électrique pour équiper votre tableau de manière optimale.",
  "sku": "419134",
  "ean": "3414970366665",
  "brand": "Legrand",
  "productType": "Disjoncteur modulaire",
  "category": "Tableaux & Protection électrique",
  "categories": [
    "Tableaux & Protection électrique",
    "Disjoncteurs / Appareils de protection",
    "Disjoncteurs modulaires"
  ],
  "tags": [
    "disjoncteur modulaire",
    "RX3",
    "1P",
    "B10",
    "6000A",
    "BIC",
    "réf. 419134",
    "disjoncteur unipolaire",
    "appareil de protection électrique"
  ],
  "attributes": [
    {
      "name": "Gamme",
      "options": [
        "RX3"
      ]
    },
    {
      "name": "Nombre de pôles",
      "options": [
        "1P"
      ]
    },
    {
      "name": "Calibre",
      "options": [
        "B10"
      ]
    },
    {
      "name": "Capacité de coupure",
      "options": [
        "6000A"
      ]
    },
    {
      "name": "Type",
      "options": [
        "Disjoncteur modulaire"
      ]
    },
    {
      "name": "Désignation",
      "options": [
        "BIC"
      ]
    }
  ],
  "technicalSpecifications": [
    {
      "name": "Référence",
      "value": "419134"
    },
    {
      "name": "EAN",
      "value": "3414970366665"
    },
    {
      "name": "Gamme",
      "value": "RX3"
    },
    {
      "name": "Type de produit",
      "value": "Disjoncteur modulaire"
    },
    {
      "name": "Nombre de pôles",
      "value": "1P (unipolaire)"
    },
    {
      "name": "Calibre",
      "value": "B10"
    },
    {
      "name": "Capacité de coupure",
      "value": "6000A"
    },
    {
      "name": "Désignation",
      "value": "BIC"
    }
  ],
  "seo": {
    "metaTitle": "Disjoncteur modulaire RX3 1P B10 6000A - Réf. 419134",
    "metaDescription": "Disjoncteur modulaire RX3 1P B10 6000A BIC - Référence 419134, EAN 3414970366665. Protection électrique fiable pour vos tableaux.",
    "slug": "disjoncteur-modulaire-rx3-1p-b10-6000a-bic-419134",
    "focusKeyword": "disjoncteur modulaire RX3",
    "keywords": [
      "disjoncteur modulaire RX3",
      "disjoncteur 1P B10",
      "disjoncteur 6000A",
      "disjoncteur modulaire 419134",
      "appareil de protection électrique",
      "disjoncteur BIC",
      "RX3 disjoncteur"
    ]
  },
  "image": {
    "prompt": "Photographie professionnelle d'un disjoncteur modulaire électrique unipolaire de la gamme RX3, couleur grise ou noire, format DIN modulaire standard, présenté isolé sur fond blanc, éclairage studio uniforme, vue de face et de profil, sans texte sur l'image, style e-commerce haut de gamme.",
    "alt": "Disjoncteur modulaire RX3 unipolaire 1P B10 6000A - Référence 419134"
  },
  "faq": [
    {
      "question": "Quelle est la référence de ce disjoncteur ?",
      "answer": "La référence fabricant de ce disjoncteur est 419134."
    },
    {
      "question": "Quelle est la référence EAN de ce produit ?",
      "answer": "L'EAN de ce produit est 3414970366665."
    },
    {
      "question": "De quelle gamme provient ce disjoncteur ?",
      "answer": "Ce disjoncteur fait partie de la gamme RX3."
    },
    {
      "question": "Combien de pôles ce disjoncteur possède-t-il ?",
      "answer": "Ce disjoncteur est unipolaire (1P)."
    },
    {
      "question": "Quel est le calibre de ce disjoncteur ?",
      "answer": "Le calibre de ce disjoncteur est B10."
    },
    {
      "question": "Quelle est la capacité de coupure de ce disjoncteur ?",
      "answer": "La capacité de coupure de ce disjoncteur est de 6000A."
    },
    {
      "question": "Quel type de produit est-ce ?",
      "answer": "Il s'agit d'un disjoncteur modulaire, destiné à être installé dans un tableau de distribution électrique."
    }
  ],
  "schema": {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Disjoncteur modulaire RX3 1P B10 6000A BIC - Réf. 419134",
    "description": "Disjoncteur modulaire de la gamme RX3, référence 419134, unipolaire (1P), calibre B10, capacité de coupure 6000A, désignation BIC. EAN : 3414970366665.",
    "sku": "419134",
    "gtin": "3414970366665",
    "brand": {
      "@type": "Brand",
      "name": "Legrand"
    }
  }
}
JSON;

       return StepResult::continue([
            'message' => __('Réponse de l’IA reçue avec succès.', MY_AI_AGENT_DOMAIN),
            'ai_response' => $test_result,// $result->text(),
            'ai_provider' => 'openrouter',// $result->provider(),
            'ai_model' => 'minimax/minimax-m3:free',// $result->model(),
            'ai_finish_reason' => '',// $result->finishReason(),
            'ai_usage' => [],// $result->usage(),
        ]);
    }

}
