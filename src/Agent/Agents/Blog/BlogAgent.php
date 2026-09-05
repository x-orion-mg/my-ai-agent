<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog;

use InvalidArgumentException;
use MyAIAgent\Agent\AgentInterface;
use MyAIAgent\Agent\Agents\Blog\Steps\AskAiStep;
use MyAIAgent\Agent\Agents\Blog\Steps\BuildPromptStep;
use MyAIAgent\Agent\Agents\Blog\Steps\HumanValidationStep;
use MyAIAgent\Agent\Agents\Blog\Steps\ProcessAiResponseStep;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\Agents\Blog\Steps\ValidateInputStep;
use MyAIAgent\Agent\Agents\Blog\Steps\CreateBlogStep;


final class BlogAgent implements AgentInterface
{

    public function __construct(
        private readonly ValidateInputStep $validateInputStep,
        private readonly BuildPromptStep $buildPromptStep,
        private readonly AskAiStep $askAiStep,
        private readonly ProcessAiResponseStep $processAiResponseStep,
        private readonly HumanValidationStep $humanValidationStep,
        private readonly CreateBlogStep $createBlogStep
    ) {
    }
    public function id(): string
    {
        return 'generate-blog';
    }

    public function name(): string
    {
        return __('Générateur d’articles de blog', MY_AI_AGENT_DOMAIN);
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
            $this->humanValidationStep,
            $this->createBlogStep,
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
