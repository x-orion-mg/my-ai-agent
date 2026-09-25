<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\Agents\Product\Steps\CreateProductStep;
use MyAIAgent\Agent\Agents\Product\Steps\GetProductOfficialStep;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\Steps\AskAiStep;
use MyAIAgent\Agent\Steps\ProcessAiResponseStep;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep;



final readonly class ProductAgent implements AgentInterface
{

    public function __construct(
        private GetProductOfficialStep     $getProductOfficialStep,
        private BuildPromptStep       $buildPromptStep,
        private AskAiStep             $askAiStep,
        private ProcessAiResponseStep $processAiResponseStep,
        private CreateProductStep     $createProductStep
    ) {
    }
    public function id(): string
    {
        return 'generate-product';
    }

    public function name(): string
    {
        return __('Générateur de produits', MY_AI_AGENT_DOMAIN);
    }

    /**
     * @return array<string, mixed>
     */
    public function formSchema(): array
    {
        return [
            'reference' => [
                'type' => 'text',
                'required' => true,
            ],
            'technical_name' => [
                'type' => 'text',
                'required' => true,
            ],
            'family_name' => [
                'type' => 'text',
                'required' => true,
            ],
            'ean' => [
                'type' => 'text',
                'required' => true,
            ],
            'normal_price' => [
                'type' => 'number',
                'required' => true,
            ],
            'promotional_price' => [
                'type' => 'number',
                'required' => true,
            ],
            'language' => [
                'type' => 'select',
                'required' => true,
            ],
            'tone' => [
                'type' => 'select',
                'required' => true,
            ],
            'provider' => [
                'type' => 'select',
                'required' => true,
            ],
            'prompt' => [
                'type' => 'select',
                'required' => true,
            ],
        ];
    }

    /**
     * @return array<int, AgentStepInterface>
     */
    public function steps(): array
    {
        return [
            $this->getProductOfficialStep,
            $this->buildPromptStep,
            $this->askAiStep,
            $this->processAiResponseStep,
            $this->createProductStep,
        ];
    }


    /**
     * Validation minimale avant création de l'exécution.
     *
     * @param array<string, mixed> $input
     */
    public function validate(array &$input): array
    {
        $inputRequeried = [
            'reference',
            'technical_name',
            'family_name',
            'ean',
            'language',
            'tone',
            'provider',
            'prompt'
        ];
        foreach ($inputRequeried as $field) {
            if (
                !isset($input[$field])
                || trim((string) $input[$field]) === ''
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        __('Le champ "%s" est obligatoire.', MY_AI_AGENT_DOMAIN),
                        $field
                    )
                );
            }
        }
        return $input;
    }
}
