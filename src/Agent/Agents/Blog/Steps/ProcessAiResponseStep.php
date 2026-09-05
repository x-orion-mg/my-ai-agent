<?php
declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Steps;

use JsonException;
use MyAIAgent\Agent\AgentStepInterface;
use MyAIAgent\Agent\AgentContext;
use MyAIAgent\Agent\StepResult;
final class ProcessAiResponseStep implements AgentStepInterface {
    public function id(): string {
        return 'parse-ai-response';
    }
    public function label(): string {
        return 'Analyse de la réponse IA';
    }
    public function execute(AgentContext $context): StepResult {
        $response = $context->get('ai_response');
        if (! is_string($response) || trim($response) === '') {
            return StepResult::failed( 'La réponse de l\'IA est vide ou manquante.' );
        }
        $json = $this->cleanJsonResponse($response);
        try {
            /** @var mixed $decoded */
            $decoded = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
        } catch (JsonException $exception) {
            return StepResult::failed( sprintf( 'La réponse de l\'IA contient un JSON invalide : %s', $exception->getMessage() ) );
        }
        if (! is_array($decoded)) {
            return StepResult::failed( 'La réponse de l\'IA doit être un objet JSON.' );
        }
        $validationError = $this->validateStructure($decoded);
        if ($validationError !== null) {
            return StepResult::failed($validationError);
        }
        return StepResult::continue([ 'ai_response_raw' => $response, 'blog' => $decoded, ]);
    }
    private function cleanJsonResponse(string $response): string {
        $response = trim($response);
        /* * Certains modèles retournent : * * ```json * {...} * ``` * *
        Même si le prompt interdit Markdown, on accepte ce format * pour rendre le pipeline plus robuste. */
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $response, $matches)) {
            $response = trim($matches[1]);
        }
        return $response;
    }
    /** * @param array<string|int, mixed> $data */
    private function validateStructure(array $data): ?string {
        $requiredFields = [ 'postTitle', 'excerpt', 'content', 'seo', 'image', 'faq', 'suggestedTags', 'suggestedCategories', ];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $data)) {
                return sprintf( 'Le champ "%s" est manquant dans la réponse de l\'IA.', $field );
            }
        }
        if (! is_string($data['postTitle']) || trim($data['postTitle']) === '') {
            return 'Le champ "postTitle" doit être une chaîne non vide.';
        }
        if (! is_string($data['excerpt'])) {
            return 'Le champ "excerpt" doit être une chaîne.';
        }
        if (! is_string($data['content']) || trim($data['content']) === '') {
            return 'Le champ "content" doit être une chaîne HTML non vide.';
        }
        if (! is_array($data['seo'])) {
            return 'Le champ "seo" doit être un objet JSON.'; }
        if (! is_array($data['image'])) {
            return 'Le champ "image" doit être un objet JSON.';
        }
        if (! is_array($data['faq'])) {
            return 'Le champ "faq" doit être un tableau.';
        }
        if (! is_array($data['suggestedTags'])) {
            return 'Le champ "suggestedTags" doit être un tableau.';
        }
        if (! is_array($data['suggestedCategories'])) {
            return 'Le champ "suggestedCategories" doit être un tableau.';
        }
        $seoError = $this->validateSeo($data['seo']);
        if ($seoError !== null) {
            return $seoError;
        }
        $imageError = $this->validateImage($data['image']);
        if ($imageError !== null) {
            return $imageError;
        }
        $faqError = $this->validateFaq($data['faq']);
        if ($faqError !== null) {
            return $faqError;
        }
        return null;
    }
    /** * @param array<string|int, mixed> $seo */
    private function validateSeo(array $seo): ?string {

        $requiredFields = [ 'metaTitle', 'metaDescription', 'slug', 'focusKeyword', 'keywords', ];
        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $seo)) {
                return sprintf( 'Le champ SEO "%s" est manquant.', $field );
            }
        }
        if (! is_string($seo['metaTitle'])) {
            return 'Le champ SEO "metaTitle" doit être une chaîne.';
        }
        if (! is_string($seo['metaDescription'])) {
            return 'Le champ SEO "metaDescription" doit être une chaîne.';
        }
        if (! is_string($seo['slug'])) {
            return 'Le champ SEO "slug" doit être une chaîne.';
        }
        if (! is_string($seo['focusKeyword'])) {
            return 'Le champ SEO "focusKeyword" doit être une chaîne.';
        }
        if (! is_array($seo['keywords'])) {
            return 'Le champ SEO "keywords" doit être un tableau.';
        }
        return null;
    }

    /** * @param array<string|int, mixed> $image */
    private function validateImage(array $image): ?string {
        if (! array_key_exists('prompt', $image)) {
            return 'Le champ image "prompt" est manquant.';
        }
        if (! array_key_exists('alt', $image)) {
            return 'Le champ image "alt" est manquant.';
        }
        if (! is_string($image['prompt'])) {
            return 'Le champ image "prompt" doit être une chaîne.';
        }
        if (! is_string($image['alt'])) {
            return 'Le champ image "alt" doit être une chaîne.';
        }
        return null;
    }



    /** * @param array<int|string, mixed> $faq */
    private function validateFaq(array $faq): ?string {

        foreach ($faq as $index => $item) {

            if (! is_array($item)) {

                return sprintf( 'L\'élément FAQ "%s" doit être un objet.', (string) $index );
            }
            if (! array_key_exists('question', $item)) {
                return sprintf( 'La question FAQ "%s" est manquante.', (string) $index );
            }
            if (! array_key_exists('answer', $item)) {
                return sprintf( 'La réponse FAQ "%s" est manquante.', (string) $index );
            }
            if (! is_string($item['question'])) {
                return sprintf( 'La question FAQ "%s" doit être une chaîne.', (string) $index );
            }
            if (! is_string($item['answer'])) {
                return sprintf( 'La réponse FAQ "%s" doit être une chaîne.', (string) $index );
            }
        }
        return null;
    }
}