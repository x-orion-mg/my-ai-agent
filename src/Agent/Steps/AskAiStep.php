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
        //$mode = 'test';
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
  "productName": "Disjoncteur 1P 10 A courbe B 6 kA - RX³ - LEGRAND - 419134",
  "shortDescription": "<p>Disjoncteur magnétothermique RX³ 1P 10 A à courbe B, destiné à protéger les installations électriques contre les surcharges et les courts-circuits. Il se monte sur rail DIN et présente un pouvoir de coupure de 6 kA selon IEC 60898-1.</p>",
  "description": "<h2>Présentation du produit</h2><p>Le disjoncteur LEGRAND RX³ référence 419134 est un disjoncteur magnétothermique unipolaire de calibre 10 A et de courbe B. Il est destiné à la protection des installations de câblage contre les surcharges et les courts-circuits et peut également être utilisé pour contrôler et isoler l'installation.</p><h2>Utilisation</h2><p>Ce disjoncteur est conçu pour les installations électriques et se fixe sur un rail DIN. Il peut être alimenté par câble cuivre, peigne à broches ou peigne à fourches selon les possibilités prévues par le fabricant.</p><h2>Caractéristiques principales</h2><ul><li><strong>Gamme :</strong> RX³</li><li><strong>Calibre :</strong> 10 A</li><li><strong>Nombre de pôles :</strong> 1P</li><li><strong>Courbe :</strong> B</li><li><strong>Pouvoir de coupure :</strong> 6 kA selon IEC 60898-1</li><li><strong>Tension assignée :</strong> 230 V</li><li><strong>Largeur :</strong> 1 module</li><li><strong>Montage :</strong> rail DIN</li><li><strong>Indice de protection :</strong> IP20</li></ul>",
  "caracteristiquesTechnique": "<table><tr><th>Caractéristique</th><th>Valeur</th></tr><tr><td>Gamme</td><td>RX³</td></tr><tr><td>Nombre de pôles</td><td>1</td></tr><tr><td>Nombre de pôles protégés</td><td>1</td></tr><tr><td>Courant nominal</td><td>10 A</td></tr><tr><td>Courbe de déclenchement</td><td>B</td></tr><tr><td>Tension assignée</td><td>230 V</td></tr><tr><td>Tension d'isolement assignée</td><td>500 V</td></tr><tr><td>Tension assignée de tenue aux chocs</td><td>4 kV</td></tr><tr><td>Pouvoir de coupure selon EN 60898 à 230 V</td><td>6 kA</td></tr><tr><td>Pouvoir de coupure selon IEC 60947-2 à 230 V</td><td>6 kA</td></tr><tr><td>Type de tension</td><td>AC</td></tr><tr><td>Fréquence</td><td>50-60 Hz</td></tr><tr><td>Classe de limitation d'énergie</td><td>3</td></tr><tr><td>Largeur</td><td>17,7 mm</td></tr><tr><td>Hauteur</td><td>88,5 mm</td></tr><tr><td>Profondeur</td><td>77,8 mm</td></tr><tr><td>Indice de protection</td><td>IP20</td></tr><tr><td>Indice de résistance aux chocs</td><td>IK02</td></tr><tr><td>Température de stockage</td><td>-40 à 70 °C</td></tr><tr><td>Tension nominale</td><td>210 à 250 V</td></tr><tr><td>Mode de pose</td><td>Rail DIN</td></tr><tr><td>Type de connexion</td><td>Borne à vis, peigne et câble</td></tr><tr><td>Couleur</td><td>Gris</td></tr><tr><td>Référence RAL</td><td>7035</td></tr></table>",
  "sku": "419134",
  "ean": "3414970366665",
  "brand": "Legrand",
  "productType": "Disjoncteur magnétothermique",
  "category": "PROTECTION & SÉCURITÉ ÉLECTRIQUE > Disjoncteurs > Disjoncteurs divisionnaires",
  "categories": [
    "PROTECTION & SÉCURITÉ ÉLECTRIQUE",
    "Disjoncteurs",
    "Disjoncteurs divisionnaires"
  ],
  "tags": [
    "RX³",
    "disjoncteur",
    "10 A",
    "1P",
    "courbe B",
    "6 kA"
  ],
  "attributes": [
    {
      "name": "Gamme",
      "options": [
        "RX³"
      ]
    },
    {
      "name": "Courant nominal",
      "options": [
        "10 A"
      ]
    },
    {
      "name": "Nombre de pôles",
      "options": [
        "1P"
      ]
    },
    {
      "name": "Courbe",
      "options": [
        "B"
      ]
    },
    {
      "name": "Pouvoir de coupure",
      "options": [
        "6 kA"
      ]
    },
    {
      "name": "Tension assignée",
      "options": [
        "230 V"
      ]
    },
    {
      "name": "Largeur",
      "options": [
        "1 module"
      ]
    },
    {
      "name": "Indice de protection",
      "options": [
        "IP20"
      ]
    },
    {
      "name": "Mode de pose",
      "options": [
        "Rail DIN"
      ]
    }
  ],
  "technicalSpecifications": [
    {
      "name": "Nombre de pôles",
      "value": "1"
    },
    {
      "name": "Nombre de pôles protégés",
      "value": "1"
    },
    {
      "name": "Courant nominal assigné (In)",
      "value": "10 A"
    },
    {
      "name": "Caractéristique de déclenchement",
      "value": "B"
    },
    {
      "name": "Tension assignée (Ue)",
      "value": "230 V"
    },
    {
      "name": "Tension d'isolement assignée (Ui)",
      "value": "500 V"
    },
    {
      "name": "Tension assignée de tenue aux chocs (Uimp)",
      "value": "4 kV"
    },
    {
      "name": "Pouvoir de coupure assigné selon EN 60898 à 230 V (Icn)",
      "value": "6 kA"
    },
    {
      "name": "Pouvoir de coupure selon IEC 60947-2 à 230 V (Icu)",
      "value": "6 kA"
    },
    {
      "name": "Type de tension",
      "value": "AC"
    },
    {
      "name": "Fréquence",
      "value": "50-60 Hz"
    },
    {
      "name": "Classe de limitation d'énergie (I²t)",
      "value": "3"
    },
    {
      "name": "Largeur",
      "value": "17,7 mm"
    },
    {
      "name": "Hauteur",
      "value": "88,5 mm"
    },
    {
      "name": "Profondeur",
      "value": "77,8 mm"
    },
    {
      "name": "Indice de protection",
      "value": "IP20"
    },
    {
      "name": "Résistance aux chocs",
      "value": "IK02"
    },
    {
      "name": "Température de stockage",
      "value": "-40 à 70 °C"
    },
    {
      "name": "Tension nominale (Un)",
      "value": "210 à 250 V"
    },
    {
      "name": "Sens de l'alimentation électrique",
      "value": "Par le haut ou le bas"
    },
    {
      "name": "Couleur",
      "value": "Gris"
    },
    {
      "name": "Numéro RAL",
      "value": "7035"
    },
    {
      "name": "Type de connexion",
      "value": "Borne à vis, peigne et câble"
    },
    {
      "name": "Mode de pose",
      "value": "Rail DIN"
    }
  ],
  "seo": {
    "metaTitle": "Disjoncteur RX³ 10A courbe B 6kA LEGRAND 419134",
    "metaDescription": "Disjoncteur LEGRAND RX³ 1P 10 A courbe B, pouvoir de coupure 6 kA, montage sur rail DIN. Référence 419134.",
    "slug": "disjoncteur-1p-10a-courbe-b-6ka-legrand-419134",
    "focusKeyword": "disjoncteur RX3 10A 1P",
    "keywords": [
      "disjoncteur RX3",
      "Legrand 419134",
      "disjoncteur 10 A",
      "disjoncteur 1P",
      "courbe B",
      "6 kA",
      "RX³"
    ]
  },
  "image": {
    "prompt": "Image produit e-commerce réaliste d'un disjoncteur modulaire LEGRAND RX³ référence 419134, modèle unipolaire 10 A courbe B, 1 module, finition grise, destiné au montage sur rail DIN. Vue trois-quarts sur fond blanc neutre, produit seul, proportions et apparence conformes à un disjoncteur modulaire RX³, sans texte ajouté ni accessoires.",
    "alt": "Disjoncteur LEGRAND RX³ 1P 10 A courbe B 6 kA référence 419134"
  },
  "faq": [
    {
      "question": "À quoi sert le disjoncteur LEGRAND 419134 ?",
      "answer": "Il est destiné à protéger les installations de câblage contre les surcharges et les courts-circuits. Il peut également être utilisé pour contrôler et isoler l'installation."
    },
    {
      "question": "Quel est le calibre du disjoncteur 419134 ?",
      "answer": "Le courant nominal assigné du disjoncteur est de 10 A."
    },
    {
      "question": "Combien de pôles possède le LEGRAND 419134 ?",
      "answer": "Le disjoncteur possède 1 pôle, avec 1 pôle protégé."
    },
    {
      "question": "Quelle est la courbe du disjoncteur 419134 ?",
      "answer": "Le disjoncteur possède une caractéristique de déclenchement courbe B."
    },
    {
      "question": "Quel est son pouvoir de coupure ?",
      "answer": "Son pouvoir de coupure est de 6 kA selon IEC 60898-1 à 230 V."
    },
    {
      "question": "Quelle est la gamme du produit ?",
      "answer": "Le produit appartient à la gamme RX³ de LEGRAND."
    },
    {
      "question": "Comment se monte le disjoncteur 419134 ?",
      "answer": "Il se monte sur un rail DIN."
    }
  ],
  "schema": {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "Disjoncteur 1P 10 A courbe B 6 kA - RX³ - LEGRAND - 419134",
    "description": "Disjoncteur magnétothermique LEGRAND RX³ 1P 10 A à courbe B, destiné à protéger les installations électriques contre les surcharges et les courts-circuits.",
    "sku": "419134",
    "gtin": "3414970366665",
    "brand": {
      "@type": "Brand",
      "name": "Legrand"
    }
  },
  "quality": {
    "confidence": "high",
    "needsReview": true,
    "reviewReason": "La référence 419134 est identifiée avec certitude sur une page officielle LEGRAND comme un disjoncteur RX³ 1P 10 A courbe B 6 kA. Toutefois, l'EAN fourni en entrée (3414970366634) correspond officiellement à la référence 419133, tandis que la page officielle LEGRAND de la référence 419134 indique l'EAN 3414970366665. L'EAN retenu est donc celui vérifié sur la source officielle."
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
