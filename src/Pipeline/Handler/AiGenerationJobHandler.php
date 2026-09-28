<?php
declare(strict_types=1);

namespace MyAIAgent\Pipeline\Handler;

use MyAIAgent\AI\AIService;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\Agents\Product\Steps\BuildPromptStep;
use MyAIAgent\Agent\StepResult;
use MyAIAgent\Pipeline\Contract\JobHandlerInterface;
use MyAIAgent\Pipeline\DTO\ProductJob;
use MyAIAgent\Pipeline\Exception\PermanentJobException;
use MyAIAgent\Pipeline\Exception\RetryableJobException;
use MyAIAgent\Services\Settings;
use Throwable;

final class AiGenerationJobHandler implements JobHandlerInterface
{
    public function __construct(
        private readonly AIService $ai,
        private readonly BuildPromptStep $promptBuilder,
        private readonly Settings $settings,
    ) {
    }

    public function handle(ProductJob $job): array
    {
        $row = $job->payload['import_row'] ?? [];
        $source = $job->payload['source'] ?? null;

        if (!is_array($row) || !is_array($source)) {
            throw new PermanentJobException(
                'Les données import/source sont absentes du job AI.'
            );
        }

        $promptId = (int) ($row['prompt_id'] ?? $this->settings->get('default_prompt_id', 0));

        if ($promptId <= 0) {
            throw new PermanentJobException(
                'Aucun prompt produit n’est configuré.'
            );
        }

        $context = new AgentContext(
            executionId: 'pipeline-' . $job->id,
            input: [
                'reference' => $job->reference,
                'technical_name' => $row['label'] ?? '',
                'family_name' => $row['family_name'] ?? '',
                'ean' => $row['ean'] ?? '',
                'language' => $this->settings->get('language', 'fr'),
            ],
            data: [
                'prompt' => $promptId,
                'product' => $this->sourceObject($source),
            ],
            ai: $this->ai,
        );

        try {
            $result = $this->promptBuilder->execute($context);
        } catch (Throwable $exception) {
            throw new PermanentJobException(
                'Impossible de construire le prompt : ' . $exception->getMessage(),
                previous: $exception
            );
        }

        if (!$result instanceof StepResult || !$result->shouldContinue()) {
            throw new PermanentJobException(
                'La construction du prompt produit a échoué.'
            );
        }

        $prompt = $result->data()['prompt'] ?? null;

        if (!is_string($prompt) || trim($prompt) === '') {
            throw new PermanentJobException(
                'Le prompt généré est vide.'
            );
        }

        try {
            $response = $this->ai->ask(
                (string) $this->settings->get('default_provider', 'openai'),
                $prompt
            );
        } catch (Throwable $exception) {
            throw new RetryableJobException(
                'Erreur du fournisseur IA : ' . $exception->getMessage(),
                previous: $exception
            );
        }

        return [
            'ai' => [
                'text' => $response->text(),
                'provider' => $response->provider(),
                'model' => $response->model(),
                'finishReason' => $response->finishReason(),
                'usage' => $response->usage(),
            ],
        ];
    }

    private function sourceObject(array $source): object
    {
        return new class($source) {
            public function __construct(private readonly array $data) {}

            public function __get(string $name): mixed
            {
                return $this->data[$name] ?? null;
            }
        };
    }
}
