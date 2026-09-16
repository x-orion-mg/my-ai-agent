<?php

declare(strict_types=1);

namespace MyAIAgent\Agent\Agents\Product\Response;

use MyAIAgent\Agent\Response\AiResponseValidationException;
use MyAIAgent\Agent\Response\AiResponseValidatorInterface;

final class ProductResponseValidator implements AiResponseValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $this->validateRequiredFields($data);
        $this->validateMainFields($data);
        $this->validateCategories($data);
        $this->validateTags($data);
        $this->validateAttributes($data);
        $this->validateTechnicalSpecifications($data);
        $this->validateSeo($data['seo']);
        $this->validateImage($data['image']);
        $this->validateFaq($data['faq']);
        $this->validateSchema($data['schema']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateRequiredFields(array $data): void
    {
        $requiredFields = [
            'productName',
            'shortDescription',
            'description',
            'sku',
            'ean',
            'brand',
            'productType',
            'category',
            'categories',
            'tags',
            'attributes',
            'technicalSpecifications',
            'seo',
            'image',
            'faq',
            'schema',
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
        $this->assertNonEmptyString($data, 'productName');
        $this->assertString($data, 'shortDescription');
        $this->assertNonEmptyString($data, 'description');

        $this->assertNullableString($data, 'sku');
        $this->assertNullableString($data, 'brand');

        $this->assertString($data, 'ean');
        $this->assertNonEmptyString($data, 'productType');
        $this->assertNonEmptyString($data, 'category');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCategories(array $data): void
    {
        $this->validateStringList($data, 'categories');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateTags(array $data): void
    {
        $this->validateStringList($data, 'tags');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateAttributes(array $data): void
    {
        $this->assertArray($data, 'attributes');

        foreach ($data['attributes'] as $index => $attribute) {
            if (!is_array($attribute)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'L\'attribut "%s" doit être un objet.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('name', $attribute)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le nom de l\'attribut "%s" est manquant.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('options', $attribute)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Les options de l\'attribut "%s" sont manquantes.',
                        (string)$index
                    )
                );
            }

            $this->assertNonEmptyString($attribute, 'name');

            if (!is_array($attribute['options'])) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le champ "options" de l\'attribut "%s" doit être un tableau.',
                        (string)$index
                    )
                );
            }

            foreach ($attribute['options'] as $optionIndex => $option) {
                if (!is_string($option)) {
                    throw new AiResponseValidationException(
                        sprintf(
                            'L\'option "%s" de l\'attribut "%s" doit être une chaîne.',
                            (string)$optionIndex,
                            (string)$index
                        )
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateTechnicalSpecifications(array $data): void
    {
        $this->assertArray($data, 'technicalSpecifications');

        foreach ($data['technicalSpecifications'] as $index => $specification) {
            if (!is_array($specification)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'La spécification technique "%s" doit être un objet.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('name', $specification)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le nom de la spécification technique "%s" est manquant.',
                        (string)$index
                    )
                );
            }

            if (!array_key_exists('value', $specification)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'La valeur de la spécification technique "%s" est manquante.',
                        (string)$index
                    )
                );
            }

            $this->assertNonEmptyString($specification, 'name');
            $this->assertString($specification, 'value');
        }
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
        $this->assertNonEmptyString($seo, 'slug');
        $this->assertString($seo, 'focusKeyword');

        if (!is_array($seo['keywords'])) {
            throw new AiResponseValidationException(
                'Le champ SEO "keywords" doit être un tableau.'
            );
        }

        foreach ($seo['keywords'] as $index => $keyword) {
            if (!is_string($keyword)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le mot-clé SEO "%s" doit être une chaîne.',
                        (string)$index
                    )
                );
            }
        }
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

        $requiredFields = [
            'prompt',
            'alt',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $image)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le champ image "%s" est manquant.',
                        $field
                    )
                );
            }
        }

        $this->assertNonEmptyString($image, 'prompt');
        $this->assertNonEmptyString($image, 'alt');
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

            $this->assertNonEmptyString($item, 'question');
            $this->assertNonEmptyString($item, 'answer');
        }
    }

    /**
     * @param mixed $schema
     */
    private function validateSchema(mixed $schema): void
    {
        if (!is_array($schema)) {
            throw new AiResponseValidationException(
                'Le champ "schema" doit être un objet JSON.'
            );
        }

        $requiredFields = [
            '@context',
            '@type',
            'name',
            'description',
            'sku',
            'gtin',
            'brand',
        ];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $schema)) {
                throw new AiResponseValidationException(
                    sprintf(
                        'Le champ Schema.org "%s" est manquant.',
                        $field
                    )
                );
            }
        }

        $this->assertString($schema, '@context');
        $this->assertString($schema, '@type');
        $this->assertNonEmptyString($schema, 'name');
        $this->assertString($schema, 'description');

        $this->assertNullableString($schema, 'sku');
        $this->assertString($schema, 'gtin');

        if (!is_array($schema['brand'])) {
            throw new AiResponseValidationException(
                'Le champ Schema.org "brand" doit être un objet JSON.'
            );
        }

        if (!array_key_exists('@type', $schema['brand'])) {
            throw new AiResponseValidationException(
                'Le champ "@type" de "brand" est manquant.'
            );
        }

        if (!array_key_exists('name', $schema['brand'])) {
            throw new AiResponseValidationException(
                'Le champ "name" de "brand" est manquant.'
            );
        }

        $this->assertString($schema['brand'], '@type');
        $this->assertNullableString($schema['brand'], 'name');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateStringList(
        array $data,
        string $field
    ): void {
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
        array $data,
        string $field
    ): void {
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
    private function assertNullableString(
        array $data,
        string $field
    ): void {
        if (!array_key_exists($field, $data)) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" est manquant.',
                    $field
                )
            );
        }

        if ($data[$field] !== null && !is_string($data[$field])) {
            throw new AiResponseValidationException(
                sprintf(
                    'Le champ "%s" doit être une chaîne ou null.',
                    $field
                )
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertNonEmptyString(
        array $data,
        string $field
    ): void {
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
        array $data,
        string $field
    ): void {
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
