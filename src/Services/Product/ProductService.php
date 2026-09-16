<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Product;

use RuntimeException;
use WC_Product_Simple;
use WP_Error;

final class ProductService
{
    /**
     * Create a WooCommerce product.
     *
     * @param array<string, mixed> $data
     *
     * @return int Product ID.
     *
     * @throws RuntimeException When the product cannot be created.
     */
    public function create(array $data): int
    {
        $productName = trim((string) ($data['productName'] ?? ''));
        $shortDescription = (string) ($data['shortDescription'] ?? '');
        $description = (string) ($data['description'] ?? '');
        $sku = $data['sku'] ?? null;
        $ean = $data['ean'] ?? null;

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

        if (!function_exists('wc_get_product')) {
            throw new RuntimeException(
                'WooCommerce n’est pas disponible.'
            );
        }

        /*
         * Création du produit WooCommerce.
         */
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

        /*
         * Produit créé en brouillon.
         */
        $product->set_status('draft');

        /*
         * SKU.
         *
         * Le JSON peut contenir null.
         */
        if (is_string($sku) && trim($sku) !== '') {
            $product->set_sku(
                sanitize_text_field($sku)
            );
        }

        /*
         * Enregistrement du produit.
         */
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

        /*
         * Catégories.
         */
        if (isset($data['categories']) && is_array($data['categories'])) {
            $this->assignCategories(
                $productId,
                $data['categories']
            );
        }

        /*
         * Tags.
         */
        if (isset($data['tags']) && is_array($data['tags'])) {
            $this->assignTags(
                $productId,
                $data['tags']
            );
        }

        /*
         * Attributs.
         */
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $this->assignAttributes(
                $productId,
                $data['attributes']
            );
        }

        /*
         * EAN.
         */
        if (is_string($ean) && trim($ean) !== '') {
            update_post_meta(
                $productId,
                '_ean',
                sanitize_text_field($ean)
            );
        }

        /*
         * Marque.
         */
        if (
            array_key_exists('brand', $data)
            && is_string($data['brand'])
            && trim($data['brand']) !== ''
        ) {
            update_post_meta(
                $productId,
                '_brand',
                sanitize_text_field($data['brand'])
            );
        }


        /*
         * Catégorie principale.
         */
        if (
            isset($data['category'])
            && is_string($data['category'])
        ) {
            update_post_meta(
                $productId,
                '_product_category_ai',
                sanitize_text_field($data['category'])
            );
        }

        /*
         * SEO.
         */
        if (isset($data['seo']) && is_array($data['seo'])) {
            $this->saveSeo(
                $productId,
                $data['seo']
            );
        }

        /*
         * FAQ.
         */
        if (isset($data['faq']) && is_array($data['faq'])) {
            $this->saveFaq(
                $productId,
                $data['faq']
            );
        }

        /*
         * Image.
         *
         * On conserve le prompt et le ALT pour le traitement
         * ultérieur de génération/import d'image.
         */
        if (isset($data['image']) && is_array($data['image'])) {
            $this->saveImageData(
                $productId,
                $data['image']
            );
        }

        /*
         * Schema.org.
         */
        if (isset($data['schema']) && is_array($data['schema'])) {
            $this->saveSchema(
                $productId,
                $data['schema']
            );
        }

        return $productId;
    }

    /**
     * Assign WooCommerce product categories.
     *
     * @param array<int, mixed> $categories
     */
    private function assignCategories(
        int $productId,
        array $categories
    ): void {
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

        if ($termIds !== []) {
            wp_set_object_terms(
                $productId,
                $termIds,
                'product_cat'
            );
        }
    }

    /**
     * Assign WooCommerce product tags.
     *
     * @param array<int, mixed> $tags
     */
    private function assignTags(
        int $productId,
        array $tags
    ): void {
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

        if ($termIds !== []) {
            wp_set_object_terms(
                $productId,
                $termIds,
                'product_tag'
            );
        }
    }

    /**
     * Create or retrieve a WordPress term.
     */
    private function getOrCreateTerm(
        string $name,
        string $taxonomy
    ): int {
        $term = term_exists(
            $name,
            $taxonomy
        );

        if (is_array($term)) {
            return (int) $term['term_id'];
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

        return (int) $result['term_id'];
    }

    /**
     * Assign WooCommerce product attributes.
     *
     * @param array<int, mixed> $attributes
     */
    private function assignAttributes(
        int $productId,
        array $attributes
    ): void {
        $product = wc_get_product($productId);

        if (!$product) {
            throw new RuntimeException(
                'Impossible de récupérer le produit pour enregistrer ses attributs.'
            );
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

            /*
             * Attribut personnalisé du produit.
             */
            $productAttribute = new \WC_Product_Attribute();

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

        if ($productAttributes !== []) {
            $product->set_attributes(
                $productAttributes
            );

            $product->save();
        }
    }

    /**
     * Save SEO information.
     *
     * @param array<string, mixed> $seo
     */
    private function saveSeo(
        int $productId,
        array $seo
    ): void {
        if (isset($seo['metaTitle']) && is_string($seo['metaTitle'])) {
            update_post_meta(
                $productId,
                '_ai_meta_title',
                sanitize_text_field($seo['metaTitle'])
            );
        }

        if (
            isset($seo['metaDescription'])
            && is_string($seo['metaDescription'])
        ) {
            update_post_meta(
                $productId,
                '_ai_meta_description',
                sanitize_textarea_field($seo['metaDescription'])
            );
        }

        if (isset($seo['slug']) && is_string($seo['slug'])) {
            wp_update_post([
                'ID' => $productId,
                'post_name' => sanitize_title($seo['slug']),
            ]);
        }

        if (
            isset($seo['focusKeyword'])
            && is_string($seo['focusKeyword'])
        ) {
            update_post_meta(
                $productId,
                '_ai_focus_keyword',
                sanitize_text_field($seo['focusKeyword'])
            );
        }

        if (isset($seo['keywords']) && is_array($seo['keywords'])) {
            $keywords = [];

            foreach ($seo['keywords'] as $keyword) {
                if (is_string($keyword)) {
                    $keyword = trim($keyword);

                    if ($keyword !== '') {
                        $keywords[] = $keyword;
                    }
                }
            }

            update_post_meta(
                $productId,
                '_ai_seo_keywords',
                $keywords
            );
        }
    }

    /**
     * Save FAQ data.
     *
     * @param array<int, mixed> $faq
     */
    private function saveFaq(
        int $productId,
        array $faq
    ): void {
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

            $cleanFaq[] = [
                'question' => sanitize_text_field(
                    $item['question']
                ),
                'answer' => wp_kses_post(
                    $item['answer']
                ),
            ];
        }

        update_post_meta(
            $productId,
            '_ai_faq',
            $cleanFaq
        );
    }

    /**
     * Save image generation information.
     *
     * @param array<string, mixed> $image
     */
    private function saveImageData(
        int $productId,
        array $image
    ): void {
        if (
            isset($image['prompt'])
            && is_string($image['prompt'])
        ) {
            update_post_meta(
                $productId,
                '_ai_image_prompt',
                sanitize_textarea_field($image['prompt'])
            );
        }

        if (
            isset($image['alt'])
            && is_string($image['alt'])
        ) {
            update_post_meta(
                $productId,
                '_ai_image_alt',
                sanitize_text_field($image['alt'])
            );
        }
    }

    /**
     * Save Schema.org data.
     *
     * @param array<string, mixed> $schema
     */
    private function saveSchema(
        int $productId,
        array $schema
    ): void {
        update_post_meta(
            $productId,
            '_ai_schema',
            $schema
        );
    }
}
