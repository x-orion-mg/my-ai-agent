<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\Agents\Product\Steps\CreateProductStep;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\Steps\AskAiStep;
use MyAIAgent\Agent\Steps\ValidateInputStep;
use MyAIAgent\Agent\Steps\ProcessAiResponseStep;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep;



final readonly class ProductAgent implements AgentInterface
{

    public function __construct(
        private ValidateInputStep     $validateInputStep,
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
            'theme' => [
                'type' => 'text',
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
            $this->validateInputStep,
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
    public function validate(array $input): void
    {
        if (
            !isset($input['theme'])
            || trim((string) $input['theme']) === ''
        ) {
            throw new InvalidArgumentException(
                __('Le thème est obligatoire.', MY_AI_AGENT_DOMAIN)
            );
        }
    }
}
