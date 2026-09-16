<?php


declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Blog\Response;

use MyAIAgent\Agent\Response\AiResponseValidationException;
use MyAIAgent\Agent\Response\AiResponseValidatorInterface;

final class BlogResponseValidator implements AiResponseValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $this->validateRequiredFields($data);
        $this->validateMainFields($data);
        $this->validateSeo($data['seo']);
        $this->validateImage($data['image']);
        $this->validateFaq($data['faq']);
        $this->validateStringList($data, 'suggestedTags');
        $this->validateStringList($data, 'suggestedCategories');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateRequiredFields(array $data): void
    {
        $requiredFields = [
            'postTitle',
            'excerpt',
            'content',
            'seo',
            'image',
            'faq',
            'suggestedTags',
            'suggestedCategories',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le champ "%s" est manquant dans la réponse de l\'IA.',
                        $field
                    )
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateMainFields(array $data): void
    {
        $this->assertNonEmptyString($data, 'postTitle');

        $this->assertString($data, 'excerpt');

        $this->assertNonEmptyString($data, 'content');
    }

    /**
     * @param mixed $seo
     */
    private function validateSeo(mixed $seo): void
    {
        if (!is_array($seo)) {
            throw new AiResponseValidationException(
                'Le champ "seo" doit être un objet JSON.'
            );
        }

        $requiredFields = [
            'metaTitle',
            'metaDescription',
            'slug',
            'focusKeyword',
            'keywords',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $seo)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le champ SEO "%s" est manquant.',
                        $field
                    )
                );
            }
        }

        $this->assertString($seo, 'metaTitle');
        $this->assertString($seo, 'metaDescription');
        $this->assertString($seo, 'slug');
        $this->assertString($seo, 'focusKeyword');
        $this->assertArray($seo, 'keywords');
    }

    /**
     * @param mixed $image
     */
    private function validateImage(mixed $image): void
    {
        if (!is_array($image)) {
            throw new AiResponseValidationException(
                'Le champ "image" doit être un objet JSON.'
            );
        }

        $this->assertString($image, 'prompt');
        $this->assertString($image, 'alt');
    }

    /**
     * @param mixed $faq
     */
    private function validateFaq(mixed $faq): void
    {
        if (!is_array($faq)) {
            throw new AiResponseValidationException(
                'Le champ "faq" doit être un tableau.'
            );
        }

        foreach ($faq as $index => $item) {
            if (!is_array($item)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'L\'élément FAQ "%s" doit être un objet.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('question', $item)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'La question FAQ "%s" est manquante.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('answer', $item)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'La réponse FAQ "%s" est manquante.',
                        (string)$index
                    )
                );
            }

            $this->assertString($item, 'question');
            $this->assertString($item, 'answer');
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateStringList(
        array  $data,
        string $field
    ): void
    {
        $this->assertArray($data, $field);

        foreach ($data[$field] as $index => $value) {
            if (!is_string($value)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'L\'élément "%s" du champ "%s" doit être une chaîne.',
                        (string)$index,
                        $field
                    )
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertString(
        array  $data,
        string $field
    ): void
    {
        if (!array_key_exists($field, $data)) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" est manquant.',
                    $field
                )
            );
        }

        if (!is_string($data[$field])) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" doit être une chaîne.',
                    $field
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertNonEmptyString(
        array  $data,
        string $field
    ): void
    {
        $this->assertString($data, $field);

        if (trim($data[$field]) === '') {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" ne peut pas être vide.',
                    $field
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertArray(
        array  $data,
        string $field
    ): void
    {
        if (!array_key_exists($field, $data)) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" est manquant.',
                    $field
                )
            );
        }

        if (!is_array($data[$field])) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" doit être un tableau.',
                    $field
                )
            );
        }
    }
}
