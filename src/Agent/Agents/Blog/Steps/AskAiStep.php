<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

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
  "postTitle": "Comment faire un gâteau : le guide simple pour tous les débutants",
  "excerpt": "Envie de faire un gâteau maison ? Découvrez notre guide pas à pas, des ingrédients aux étapes de préparation, pour réaliser un gâteau délicieux même si vous êtes débutant.",
  "content": "<p>Faire un gâteau n'a rien de compliqué quand on connaît les bonnes étapes. Que vous soyez débutant ou que vous cherchiez à perfectionner vos techniques, ce guide vous accompagne pour réaliser un gâteau moelleux et savoureux, directement dans votre cuisine.</p>\n\n<h2>Les ingrédients de base pour faire un gâteau</h2>\n\n<p>Avant de commencer, assurez-vous d'avoir tous les ingrédients nécessaires à portée de main. Voici la liste essentielle pour un gâteau classique :</p>\n\n<ul>\n<li>200 g de farine</li>\n<li>150 g de sucre</li>\n<li>3 œufs</li>\n<li>100 g de beurre fondu</li>\n<li>1 sachet de levure chimique</li>\n<li>25 cl de lait</li>\n<li>Une pincée de sel</li>\n<li>Vanille ou extrait de vanille selon vos préférences</li>\n</ul>\n\n<h2>Les étapes pour faire un gâteau</h2>\n\n<h3>Préchauffer le four</h3>\n<p>Commencez par préchauffer votre four à 180 °C. Un four bien chaud est la clé d'une cuisson uniforme et d'un gâteau parfaitement levé.</p>\n\n<h3>Préparer la pâte</h3>\n<p>Dans un grand saladier, mélangez la farine et la levure chimique. Ajoutez le sucre et la pincée de sel. Incorporez ensuite les œufs un par un en remuant vigoureusement à chaque ajout. Versez le lait petit à petit, puis ajoutez le beurre fondu. Continuez à mélanger jusqu'à obtenir une pâte lisse et homogène.</p>\n\n<h3>Verser et cuire</h3>\n<p>Beurrez et farinez un moule à gâteau. Versez la préparation délicatement dans le moule. Enfournez pour environ 30 à 35 minutes. Pour vérifier la cuisson, insérez la lame d'un couteau au centre : elle doit ressortir sèche.</p>\n\n<h2>Conseils pratiques pour réussir votre gâteau</h2>\n\n<ul>\n<li>Sortez tous les ingrédients à température ambiante avant de commencer. Cela facilite le mélange et évite les grumeaux.</li>\n<li>Ne mélangez pas trop la pâte une fois la farine ajoutée, sinon votre gâteau risque d'être compact.</li>\n<li>Laissez refroidir le gâteau dans le moule pendant 10 minutes avant de le démouler.</li>\n<li>Personnalisez votre création en ajoutant des fruits, du chocolat ou de la confiture selon vos envies.</li>\n</ul>\n\n<h2>Des variantes pour faire un gâteau original</h2>\n\n<p>Une fois la recette de base maîtrisée, vous pouvez explorer de nombreuses variantes : un gâteau au chocolat en remplaçant une partie de la farine par du cacao en poudre, un gâteau aux pommes en ajoutant des fruits frais, ou encore un gâteau citronné pour une touche acidulée et rafraîchissante.</p>\n\n<h2>Conclusion</h2>\n\n<p>Faire un gâteau est une véritable joie, accessible à tous avec un peu de patience et les bons gestes. N'hésitez pas à expérimenter, à goûter et à adapter les recettes selon vos préférences. Votre cuisine vous remerciera !</p>",
  "seo": {
    "metaTitle": "Comment faire un gâteau : guide simple pour débutants",
    "metaDescription": "Découvrez comment faire un gâteau maison grâce à notre guide pas à pas. Ingrédients, étapes et conseils pour un gâteau moelleux même en tant que débutant.",
    "slug": "comment-faire-un-gateau",
    "focusKeyword": "comment faire un gâteau",
    "keywords": [
      "comment faire un gâteau",
      "recette de gâteau facile",
      "gâteau maison",
      "fabriquer un gâteau",
      "recette gâteau débutant"
    ]
  },
  "image": {
    "prompt": "A beautiful homemade cake on a rustic wooden table, freshly baked with a golden-brown crust, sliced on one side showing a soft and moist interior, surrounded by fresh strawberries and a glass of milk, warm natural lighting streaming through a window, cozy kitchen background, food photography style, top-down angle, inviting and colorful composition, photorealistic",
    "alt": "Un gâteau maison frais et doré sur une table en bois rustique, entouré de fruits frais, éclairé par la lumière naturelle de la cuisine"
  },
  "faq": [
    {
      "question": "Quels sont les ingrédients indispensables pour faire un gâteau ?",
      "answer": "Les ingrédients de base pour faire un gâteau sont la farine, le sucre, les œufs, le beurre, la levure chimique et le lait. Ces éléments permettent de réaliser un gâteau classique moelleux et savoureux."
    },
    {
      "question": "Combien de temps faut-il pour cuire un gâteau au four ?",
      "answer": "En général, un gâteau cuit au four à 180 °C pendant 30 à 35 minutes. Le temps de cuisson peut varier selon la taille du moule et la recette utilisée. Vérifiez toujours la cuisson avec la technique du couteau."
    },
    {
      "question": "Peut-on faire un gâteau sans œufs ?",
      "answer": "Oui, il est tout à fait possible de faire un gâteau sans œufs. Vous pouvez les remplacer par des bananes écrasées, du yaourt, de la compote de pommes ou du substitut végétal. La texture sera légèrement différente mais tout aussi délicieuse."
    },
    {
      "question": "Comment conserver un gâteau maison ?",
      "answer": "Un gâteau maison se conserve à température ambiante dans une boîte hermétique pendant 2 à 3 jours. Pour une conservation plus longue, vous pouvez le congeler après l'avoir emballé dans du film alimentaire."
    }
  ],
  "suggestedTags": [
    "gâteau maison",
    "recette facile",
    "pâtisserie",
    "gâteau au four",
    "cuisine familiale"
  ],
  "suggestedCategories": [
    "Pâtisserie et cuisine"
  ]
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
