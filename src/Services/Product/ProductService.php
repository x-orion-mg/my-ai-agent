<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Product;

use RuntimeException;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Simple;

final class ProductService
{
    /**
     * Create a new product or update an existing product by SKU.
     *
     * @param array<string, mixed> $data
     *
     * @return int Product ID.
     *
     * @throws RuntimeException When the product cannot be created or updated.
     */
    public function createOrUpdate(array $data): int
    {
        $sku = isset($data['sku'])
            ? trim((string)$data['sku'])
            : '';

        if ($sku === '') {
            throw new RuntimeException(
                'Le SKU du produit est manquant.'
            );
        }

        if (!function_exists('wc_get_product')) {
            throw new RuntimeException(
                'WooCommerce n’est pas disponible.'
            );
        }

        $product = $this->findProductBySku($sku);

        if ($product !== null) {
            return $this->updateProduct(
                $product,
                $data
            );
        }

        return $this->createProduct($data);
    }

    /**
     * Create a WooCommerce product.
     *
     * @param array<string, mixed> $data
     *
     * @return int Product ID.
     *
     * @throws RuntimeException When the product cannot be created.
     */
    private function createProduct(array $data): int
    {
        $productName = trim(
            (string)($data['productName'] ?? '')
        );

        $shortDescription = (string)(
            $data['shortDescription'] ?? ''
        );

        $description = (string)(
            $data['description'] ?? ''
        );

        $sku = trim(
            (string)($data['sku'] ?? '')
        );

        if ($productName === '') {
            throw new RuntimeException(
                'Le nom du produit est manquant.'
            );
        }

        if ($description === '') {
            throw new RuntimeException(
                'La description du produit est manquante.'
            );
        }

        if ($sku === '') {
            throw new RuntimeException(
                'Le SKU du produit est manquant.'
            );
        }

        $existingProduct = $this->findProductBySku($sku);

        if ($existingProduct !== null) {
            return $this->updateProduct(
                $existingProduct,
                $data
            );
        }

        $product = new WC_Product_Simple();

        $product->set_name(
            sanitize_text_field($productName)
        );

        $product->set_short_description(
            wp_kses_post($shortDescription)
        );

        $product->set_description(
            wp_kses_post($description)
        );

        $product->set_status('draft');

        $product->set_sku(
            sanitize_text_field($sku)
        );

        try {
            $productId = $product->save();
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de créer le produit : %s',
                    $exception->getMessage()
                ),
                previous: $exception
            );
        }

        if ($productId <= 0) {
            throw new RuntimeException(
                'WooCommerce n’a pas retourné un identifiant produit valide.'
            );
        }

        $this->saveProductData(
            $productId,
            $data
        );

        return $productId;
    }

    /**
     * Update an existing WooCommerce product.
     *
     * Only changed WooCommerce properties are saved.
     *
     * @param WC_Product $product
     * @param array<string,mixed> $data
     *
     * @return int Product ID.
     *
     * @throws RuntimeException When the product cannot be updated.
     */
    private function updateProduct(
        WC_Product $product,
        array      $data
    ): int
    {
        $changed = false;

        /*
         * Nom.
         */
        if (
            isset($data['productName'])
            && is_string($data['productName'])
        ) {
            $newName = sanitize_text_field(
                trim($data['productName'])
            );

            if (
                $newName !== ''
                && $product->get_name() !== $newName
            ) {
                $product->set_name($newName);
                $changed = true;
            }
        }

        /*
         * Description courte.
         */
        if (
            array_key_exists('shortDescription', $data)
            && is_string($data['shortDescription'])
        ) {
            $newShortDescription = wp_kses_post(
                $data['shortDescription']
            );

            if (
                $product->get_short_description()
                !== $newShortDescription
            ) {
                $product->set_short_description(
                    $newShortDescription
                );

                $changed = true;
            }
        }

        /*
         * Description.
         */
        if (
            array_key_exists('description', $data)
            && is_string($data['description'])
        ) {
            $newDescription = wp_kses_post(
                $data['description']
            );

            if (
                $product->get_description()
                !== $newDescription
            ) {
                $product->set_description(
                    $newDescription
                );

                $changed = true;
            }
        }

        /*
         * SKU.
         *
         * Normalement il ne change pas puisque le SKU
         * sert à identifier le produit.
         */
        if (
            isset($data['sku'])
            && is_string($data['sku'])
        ) {
            $newSku = sanitize_text_field(
                trim($data['sku'])
            );

            if (
                $newSku !== ''
                && $product->get_sku() !== $newSku
            ) {
                $existingProductId = wc_get_product_id_by_sku(
                    $newSku
                );

                if (
                    $existingProductId
                    && (int)$existingProductId !== $product->get_id()
                ) {
                    throw new RuntimeException(
                        sprintf(
                            'Le SKU "%s" est déjà utilisé par le produit #%d.',
                            $newSku,
                            (int)$existingProductId
                        )
                    );
                }

                $product->set_sku($newSku);
                $changed = true;
            }
        }

        /*
         * Sauvegarde uniquement si une propriété WooCommerce
         * a réellement changé.
         */
        if ($changed) {
            try {
                $product->save();
            } catch (\Throwable $exception) {
                throw new RuntimeException(
                    sprintf(
                        'Impossible de mettre à jour le produit #%d : %s',
                        $product->get_id(),
                        $exception->getMessage()
                    ),
                    previous: $exception
                );
            }
        }

        /*
         * Données complémentaires.
         */
        $this->saveProductData(
            $product->get_id(),
            $data
        );

        return $product->get_id();
    }

    /**
     * Find a product by SKU.
     */
    private function findProductBySku(?string $sku): ?WC_Product
    {
        if (
            $sku === null
            || trim($sku) === ''
        ) {
            return null;
        }

        $productId = wc_get_product_id_by_sku(
            trim($sku)
        );

        if (!$productId) {
            return null;
        }

        $product = wc_get_product(
            (int)$productId
        );

        if (!$product instanceof WC_Product) {
            return null;
        }

        return $product;
    }

    /**
     * Save all additional product data.
     *
     * @param array<string,mixed> $data
     */
    private function saveProductData(int $productId, array $data): void
    {
        /*
         * EAN.
         */
        $ean = $data['ean'] ?? null;
        /*
 * EAN / GTIN WooCommerce.
 *
 * WooCommerce utilise _global_unique_id pour le GTIN/EAN.
 */
        if (
            is_string($ean)
            && trim($ean) !== ''
        ) {
            $ean = sanitize_text_field(
                trim($ean)
            );

            $existingEan = get_post_meta(
                $productId,
                '_global_unique_id',
                true
            );

            if ((string)$existingEan !== $ean) {
                update_post_meta(
                    $productId,
                    '_global_unique_id',
                    $ean
                );
            }
        }
        /*
         * Marque Legrand.
         *
         * La marque "Legrand" existe déjà dans la taxonomie
         * product_brand.
         */
        if (
            isset($data['brand'])
            && is_string($data['brand'])
            && trim($data['brand']) !== ''
        ) {
            $brand = sanitize_text_field(
                trim($data['brand'])
            );

            $term = term_exists(
                $brand,
                'product_brand'
            );

            if (is_array($term)) {
                $brandTermId = (int)$term['term_id'];
            } elseif (is_int($term)) {
                $brandTermId = $term;
            } else {
                $result = wp_insert_term(
                    $brand,
                    'product_brand'
                );

                if (is_wp_error($result)) {
                    throw new RuntimeException(
                        sprintf(
                            'Impossible de créer la marque "%s" : %s',
                            $brand,
                            $result->get_error_message()
                        )
                    );
                }

                $brandTermId = (int)$result['term_id'];
            }

            /*
             * On affecte la marque au produit.
             *
             * true = on remplace les marques existantes
             * par celle reçue dans le JSON.
             */
            wp_set_object_terms(
                $productId,
                [$brandTermId],
                'product_brand',
                false
            );
        }


        /*
         * Type de produit.
         */
        if (
            isset($data['productType'])
            && is_string($data['productType'])
            && trim($data['productType']) !== ''
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_product_type_ai',
                sanitize_text_field(
                    trim($data['productType'])
                )
            );
        }

        /*
         * Catégorie principale IA.
         */
        if (
            isset($data['category'])
            && is_string($data['category'])
            && trim($data['category']) !== ''
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_product_category_ai',
                sanitize_text_field(
                    trim($data['category'])
                )
            );
        }

        /*
         * Catégories WooCommerce.
         */
        if (
            isset($data['categories'])
            && is_array($data['categories'])
        ) {
            $this->assignCategories(
                $productId,
                $data['categories']
            );
        }

        /*
         * Tags WooCommerce.
         */
        if (
            isset($data['tags'])
            && is_array($data['tags'])
        ) {
            $this->assignTags(
                $productId,
                $data['tags']
            );
        }

        /*
         * Attributs WooCommerce.
         */
        if (
            isset($data['attributes'])
            && is_array($data['attributes'])
        ) {
            $this->assignAttributes(
                $productId,
                $data['attributes']
            );
        }

        /*
         * Spécifications techniques.
         */
        if (
            isset($data['technicalSpecifications'])
            && is_array($data['technicalSpecifications'])
        ) {
            $cleanSpecifications = [];

            foreach (
                $data['technicalSpecifications']
                as $specification
            ) {
                if (!is_array($specification)) {
                    continue;
                }

                $name = $specification['name'] ?? null;
                $value = $specification['value'] ?? null;

                if (
                    !is_string($name)
                    || !is_string($value)
                    || trim($name) === ''
                ) {
                    continue;
                }

                $cleanSpecifications[] = [
                    'name' => sanitize_text_field(
                        trim($name)
                    ),
                    'value' => sanitize_text_field(
                        trim($value)
                    ),
                ];
            }

            $this->updateArrayMetaIfChanged(
                $productId,
                '_ai_technical_specifications',
                $cleanSpecifications
            );
        }

        /*
 * Lien produit Legrand.
 *
 * JSON :
 * "url": "https://www.legrand.mg/..."
 *
 * ACF :
 * legrand_product_url
 * return_format => array
 */
        if (
            isset($data['url'])
            && is_string($data['url'])
            && trim($data['url']) !== ''
        ) {
            $url = esc_url_raw(
                trim($data['url'])
            );

            if ($url !== '') {
                $existingLink = get_field(
                    'legrand_product_url',
                    $productId
                );

                $newLink = [
                    'url' => $url,
                    'title' => '',
                    'target' => '',
                ];

                /*
                 * On compare uniquement les valeurs utiles.
                 */
                $existingUrl = '';

                if (
                    is_array($existingLink)
                    && isset($existingLink['url'])
                    && is_string($existingLink['url'])
                ) {
                    $existingUrl = trim(
                        $existingLink['url']
                    );
                }

                if ($existingUrl !== $url) {
                    update_field(
                        'legrand_product_url',
                        $newLink,
                        $productId
                    );
                }
            }
        }

        /*
         * Caractéristiques techniques.
         *
         * JSON :
         * "caracteristiquesTechnique": "<table>...</table>"
         *
         * ACF :
         * technical_specs
         */
        if (
            isset($data['caracteristiquesTechnique'])
            && is_string($data['caracteristiquesTechnique'])
        ) {
            $technicalSpecs = wp_kses_post(
                $data['caracteristiquesTechnique']
            );

            $existingTechnicalSpecs = get_field(
                'technical_specs',
                $productId
            );

            if (
                !is_string($existingTechnicalSpecs)
                || $existingTechnicalSpecs !== $technicalSpecs
            ) {
                update_field(
                    'technical_specs',
                    $technicalSpecs,
                    $productId
                );
            }
        }


        /*
         * technicalData.
         */
        if (
            isset($data['technicalData'])
            && is_string($data['technicalData'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_technical_data',
                wp_kses_post(
                    $data['technicalData']
                )
            );
        }


        /*
         * Image URL source.
         */
        if (
            isset($data['image_url'])
            && is_string($data['image_url'])
            && trim($data['image_url']) !== ''
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_image_url',
                esc_url_raw(
                    trim($data['image_url'])
                )
            );
        }

        /*
         * SEO.
         */
        if (
            isset($data['seo'])
            && is_array($data['seo'])
        ) {
            $this->saveSeo(
                $productId,
                $data['seo']
            );
        }

        /*
         * FAQ.
         */
        if (
            isset($data['faq'])
            && is_array($data['faq'])
        ) {
            $this->saveFaq(
                $productId,
                $data['faq']
            );
        }

        /*
         * Image.
         */
        $imageData = [];

        if (
            isset($data['image'])
            && is_array($data['image'])
        ) {
            $imageData = $data['image'];
        }

        if (
            isset($data['image_url'])
            && is_string($data['image_url'])
        ) {
            $imageData['image_url'] = $data['image_url'];
        }

        if ($imageData !== []) {
            $this->saveImageData(
                $productId,
                $imageData
            );

            $this->importProductImage(
                $productId,
                $imageData
            );
        }

        /*
         * Schema.org.
         */
        if (
            isset($data['schema'])
            && is_array($data['schema'])
        ) {
            $this->updateArrayMetaIfChanged(
                $productId,
                '_ai_schema',
                $data['schema']
            );
        }

        /*
         * Quality.
         */
        if (
            isset($data['quality'])
            && is_array($data['quality'])
        ) {
            $this->updateArrayMetaIfChanged(
                $productId,
                '_ai_quality',
                $data['quality']
            );
        }
    }

    /**
     * Assign WooCommerce product categories.
     *
     * @param array<int,mixed> $categories
     */
    private function assignCategories(
        int   $productId,
        array $categories
    ): void
    {
        $termIds = [];

        foreach ($categories as $category) {
            if (!is_string($category)) {
                continue;
            }

            $category = trim($category);

            if ($category === '') {
                continue;
            }

            $termId = $this->getOrCreateTerm(
                $category,
                'product_cat'
            );

            $termIds[] = $termId;
        }

        $termIds = array_values(
            array_unique(
                array_map('intval', $termIds)
            )
        );

        if ($termIds === []) {
            return;
        }

        $currentTermIds = wp_get_object_terms(
            $productId,
            'product_cat',
            [
                'fields' => 'ids',
            ]
        );

        if (is_wp_error($currentTermIds)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de récupérer les catégories du produit #%d : %s',
                    $productId,
                    $currentTermIds->get_error_message()
                )
            );
        }

        $currentTermIds = array_values(
            array_unique(
                array_map('intval', $currentTermIds)
            )
        );

        sort($currentTermIds);
        sort($termIds);

        if ($currentTermIds === $termIds) {
            return;
        }

        $result = wp_set_object_terms(
            $productId,
            $termIds,
            'product_cat'
        );

        if (is_wp_error($result)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible d’assigner les catégories au produit #%d : %s',
                    $productId,
                    $result->get_error_message()
                )
            );
        }
    }

    /**
     * Assign WooCommerce product tags.
     *
     * @param array<int,mixed> $tags
     */
    private function assignTags(
        int   $productId,
        array $tags
    ): void
    {
        $termIds = [];

        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }

            $tag = trim($tag);

            if ($tag === '') {
                continue;
            }

            $termId = $this->getOrCreateTerm(
                $tag,
                'product_tag'
            );

            $termIds[] = $termId;
        }

        $termIds = array_values(
            array_unique(
                array_map('intval', $termIds)
            )
        );

        if ($termIds === []) {
            return;
        }

        $currentTermIds = wp_get_object_terms(
            $productId,
            'product_tag',
            [
                'fields' => 'ids',
            ]
        );

        if (is_wp_error($currentTermIds)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de récupérer les tags du produit #%d : %s',
                    $productId,
                    $currentTermIds->get_error_message()
                )
            );
        }

        $currentTermIds = array_values(
            array_unique(
                array_map('intval', $currentTermIds)
            )
        );

        sort($currentTermIds);
        sort($termIds);

        if ($currentTermIds === $termIds) {
            return;
        }

        $result = wp_set_object_terms(
            $productId,
            $termIds,
            'product_tag'
        );

        if (is_wp_error($result)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible d’assigner les tags au produit #%d : %s',
                    $productId,
                    $result->get_error_message()
                )
            );
        }
    }

    /**
     * Create or retrieve a WordPress term.
     */
    private function getOrCreateTerm(string $name, string $taxonomy): int
    {
        $term = term_exists(
            $name,
            $taxonomy
        );

        if (is_array($term)) {
            return (int)$term['term_id'];
        }

        if (is_int($term)) {
            return $term;
        }

        $result = wp_insert_term(
            $name,
            $taxonomy
        );

        if (is_wp_error($result)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de créer le terme "%s" : %s',
                    $name,
                    $result->get_error_message()
                )
            );
        }

        return (int)$result['term_id'];
    }

    /**
     * Assign WooCommerce product attributes.
     *
     * @param array<int,mixed> $attributes
     */
    private function assignAttributes(
        int   $productId,
        array $attributes
    ): void
    {
        $product = wc_get_product($productId);

        if (!$product instanceof WC_Product) {
            throw new RuntimeException(
                'Impossible de récupérer le produit pour enregistrer ses attributs.'
            );
        }

        if (
            !$this->attributesHaveChanged(
                $product,
                $attributes
            )
        ) {
            return;
        }

        $productAttributes = [];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $name = $attribute['name'] ?? null;
            $options = $attribute['options'] ?? null;

            if (
                !is_string($name)
                || trim($name) === ''
                || !is_array($options)
            ) {
                continue;
            }

            $cleanOptions = [];

            foreach ($options as $option) {
                if (!is_string($option)) {
                    continue;
                }

                $option = trim($option);

                if ($option !== '') {
                    $cleanOptions[] = $option;
                }
            }

            if ($cleanOptions === []) {
                continue;
            }

            $cleanOptions = array_values(
                array_unique($cleanOptions)
            );

            $productAttribute = new WC_Product_Attribute();

            $productAttribute->set_name(
                sanitize_text_field($name)
            );

            $productAttribute->set_options(
                $cleanOptions
            );

            $productAttribute->set_visible(true);
            $productAttribute->set_variation(false);

            $productAttributes[] = $productAttribute;
        }

        $product->set_attributes(
            $productAttributes
        );

        $product->save();
    }

    /**
     * Determine whether product attributes have changed.
     *
     * @param array<int,mixed> $attributes
     */
    private function attributesHaveChanged(WC_Product $product, array $attributes): bool
    {
        $current = [];

        foreach ($product->get_attributes() as $attribute) {
            if (!$attribute instanceof WC_Product_Attribute) {
                continue;
            }

            $options = $attribute->get_options();

            $options = array_map(
                static function ($option): string {
                    return (string)$option;
                },
                $options
            );

            sort($options);

            $current[] = [
                'name' => (string)$attribute->get_name(),
                'options' => $options,
            ];
        }

        $new = [];

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $name = $attribute['name'] ?? null;
            $options = $attribute['options'] ?? null;

            if (
                !is_string($name)
                || trim($name) === ''
                || !is_array($options)
            ) {
                continue;
            }

            $cleanOptions = [];

            foreach ($options as $option) {
                if (!is_string($option)) {
                    continue;
                }

                $option = trim($option);

                if ($option !== '') {
                    $cleanOptions[] = $option;
                }
            }

            $cleanOptions = array_values(
                array_unique($cleanOptions)
            );

            sort($cleanOptions);

            $new[] = [
                'name' => sanitize_text_field(
                    trim($name)
                ),
                'options' => $cleanOptions,
            ];
        }

        usort(
            $current,
            static function (
                array $a,
                array $b
            ): int {
                return $a['name'] <=> $b['name'];
            }
        );

        usort(
            $new,
            static function (
                array $a,
                array $b
            ): int {
                return $a['name'] <=> $b['name'];
            }
        );

        return $current !== $new;
    }

    /**
     * Save SEO information.
     *
     * @param array<string,mixed> $seo
     */
    private function saveSeo(
        int   $productId,
        array $seo
    ): void
    {
        if (
            isset($seo['metaTitle'])
            && is_string($seo['metaTitle'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_meta_title',
                sanitize_text_field(
                    $seo['metaTitle']
                )
            );
        }

        if (
            isset($seo['metaDescription'])
            && is_string($seo['metaDescription'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_meta_description',
                sanitize_textarea_field(
                    $seo['metaDescription']
                )
            );
        }

        if (
            isset($seo['slug'])
            && is_string($seo['slug'])
        ) {
            $newSlug = sanitize_title(
                $seo['slug']
            );

            $post = get_post($productId);

            if (
                $post
                && $post->post_name !== $newSlug
                && $newSlug !== ''
            ) {
                wp_update_post([
                    'ID' => $productId,
                    'post_name' => $newSlug,
                ]);
            }
        }

        if (
            isset($seo['focusKeyword'])
            && is_string($seo['focusKeyword'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_focus_keyword',
                sanitize_text_field(
                    $seo['focusKeyword']
                )
            );
        }

        if (
            isset($seo['keywords'])
            && is_array($seo['keywords'])
        ) {
            $keywords = [];

            foreach ($seo['keywords'] as $keyword) {
                if (!is_string($keyword)) {
                    continue;
                }

                $keyword = trim($keyword);

                if ($keyword !== '') {
                    $keywords[] = $keyword;
                }
            }

            $keywords = array_values(
                array_unique($keywords)
            );

            $this->updateArrayMetaIfChanged(
                $productId,
                '_ai_seo_keywords',
                $keywords
            );
        }
    }

    /**
     * Save FAQ data.
     *
     * @param array<int,mixed> $faq
     */
    private function saveFaq(
        int   $productId,
        array $faq
    ): void
    {
        $cleanFaq = [];

        foreach ($faq as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (
                !isset($item['question'])
                || !isset($item['answer'])
                || !is_string($item['question'])
                || !is_string($item['answer'])
            ) {
                continue;
            }

            $question = sanitize_text_field(
                trim($item['question'])
            );

            $answer = wp_kses_post(
                $item['answer']
            );

            if ($question === '') {
                continue;
            }

            $cleanFaq[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        $this->updateArrayMetaIfChanged(
            $productId,
            '_ai_faq',
            $cleanFaq
        );
    }

    /**
     * Save image generation information.
     *
     * @param array<string,mixed> $image
     */
    private function saveImageData(
        int   $productId,
        array $image
    ): void
    {
        if (
            isset($image['prompt'])
            && is_string($image['prompt'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_image_prompt',
                sanitize_textarea_field(
                    $image['prompt']
                )
            );
        }

        if (
            isset($image['alt'])
            && is_string($image['alt'])
        ) {
            $this->updateMetaIfChanged(
                $productId,
                '_ai_image_alt',
                sanitize_text_field(
                    $image['alt']
                )
            );
        }
    }

    /**
     * Import product image from image_url.
     *
     * The image is downloaded only when the source URL
     * is different from the last imported source URL.
     *
     * @param array<string,mixed> $image
     *
     * @throws RuntimeException When image download/import fails.
     */
    private function importProductImage(
        int   $productId,
        array $image
    ): void
    {
        $imageUrl = $image['image_url'] ?? null;

        if (
            !is_string($imageUrl)
            || trim($imageUrl) === ''
        ) {
            return;
        }

        $imageUrl = esc_url_raw(
            trim($imageUrl)
        );

        if ($imageUrl === '') {
            return;
        }

        $existingSourceUrl = get_post_meta(
            $productId,
            '_ai_source_image_url',
            true
        );

        /*
         * Même image déjà importée.
         */
        if (
            is_string($existingSourceUrl)
            && $existingSourceUrl === $imageUrl
        ) {
            /*
             * Vérifie tout de même qu'une miniature
             * est bien présente.
             */
            if (has_post_thumbnail($productId)) {
                return;
            }
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($imageUrl);

        if (is_wp_error($tmp)) {
            throw new RuntimeException(
                sprintf(
                    'Impossible de télécharger l’image "%s" : %s',
                    $imageUrl,
                    $tmp->get_error_message()
                )
            );
        }

        $path = parse_url(
            $imageUrl,
            PHP_URL_PATH
        );

        $filename = is_string($path)
            ? wp_basename($path)
            : '';

        if ($filename === '') {
            $filename = 'product-' . $productId . '.jpg';
        }

        $mimeType = wp_check_filetype(
            $filename
        );

        $fileType = $mimeType['type']
            ?? 'image/jpeg';

        $fileSize = filesize($tmp);

        $file = [
            'name' => sanitize_file_name(
                $filename
            ),
            'type' => $fileType,
            'tmp_name' => $tmp,
            'error' => 0,
            'size' => $fileSize !== false
                ? $fileSize
                : 0,
        ];

        $attachmentId = media_handle_sideload(
            $file,
            $productId
        );

        if (is_wp_error($attachmentId)) {
            if (file_exists($tmp)) {
                @unlink($tmp);
            }

            throw new RuntimeException(
                sprintf(
                    'Impossible d’importer l’image "%s" : %s',
                    $imageUrl,
                    $attachmentId->get_error_message()
                )
            );
        }

        /*
         * ALT.
         */
        if (
            isset($image['alt'])
            && is_string($image['alt'])
            && trim($image['alt']) !== ''
        ) {
            update_post_meta(
                $attachmentId,
                '_wp_attachment_image_alt',
                sanitize_text_field(
                    trim($image['alt'])
                )
            );
        }

        /*
         * Image principale WooCommerce.
         */
        set_post_thumbnail(
            $productId,
            $attachmentId
        );

        /*
         * URL source de la dernière image importée.
         */
        $this->updateMetaIfChanged(
            $productId,
            '_ai_source_image_url',
            $imageUrl
        );
    }

    /**
     * Save Schema.org data.
     *
     * @param array<string,mixed> $schema
     */
    private function saveSchema(
        int   $productId,
        array $schema
    ): void
    {
        $this->updateArrayMetaIfChanged(
            $productId,
            '_ai_schema',
            $schema
        );
    }

    /**
     * Update a meta value only when it has changed.
     */
    private function updateMetaIfChanged(
        int    $productId,
        string $metaKey,
        mixed  $newValue
    ): bool
    {
        $oldValue = get_post_meta(
            $productId,
            $metaKey,
            true
        );

        if ($oldValue === $newValue) {
            return false;
        }

        update_post_meta(
            $productId,
            $metaKey,
            $newValue
        );

        return true;
    }

    /**
     * Update an array meta value only when it has changed.
     *
     * @param array<mixed> $newValue
     */
    private function updateArrayMetaIfChanged(
        int    $productId,
        string $metaKey,
        array  $newValue
    ): bool
    {
        $oldValue = get_post_meta(
            $productId,
            $metaKey,
            true
        );

        if (!is_array($oldValue)) {
            $oldValue = [];
        }

        if ($oldValue === $newValue) {
            return false;
        }

        update_post_meta(
            $productId,
            $metaKey,
            $newValue
        );

        return true;
    }
}
