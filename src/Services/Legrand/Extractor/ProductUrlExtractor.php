<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Extractor;

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Support\LegrandReference;
use  MyAIAgent\Services\Legrand\Support\LegrandUrl;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

final class ProductUrlExtractor
{
    public function __construct(
        private readonly LegrandHttpClient $http
    )
    {
    }

    /**
     * Recherche l'URL officielle d'un produit Legrand
     * à partir de sa référence.
     *
     * Exemple :
     *
     * 419160
     *
     * retourne :
     *
     * https://www.legrand.mg/fr/catalogue/...
     */
    public function find(string $reference): ?string
    {
        $reference = LegrandReference::normalize($reference);

        if ($reference === '') {
            return null;
        }

        /*
         * =========================================================
         * 1. URL DE RECHERCHE LEGRAND
         * =========================================================
         */
        $searchUrl = LegrandUrl::SEARCH
            . '?search='
            . urlencode($reference);

        /*
         * =========================================================
         * 2. RÉCUPÉRATION DE LA PAGE DE RECHERCHE
         * =========================================================
         */
        try {
            $html = $this->http->get($searchUrl);
        } catch (\Throwable) {
            return null;
        }

        if ($html === '') {
            return null;
        }

        /*
         * =========================================================
         * 3. CONVERSION HTML → DOM
         * =========================================================
         */
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOWARNING
            | LIBXML_NOERROR
            | LIBXML_NONET
        );

        libxml_clear_errors();

        if (!$loaded) {
            return null;
        }

        $xpath = new DOMXPath($dom);

        /*
         * =========================================================
         * 4. RECHERCHE EXACTE DU DATA-SKU
         * =========================================================
         *
         * Exemple :
         *
         * <span
         *     class="text-product-ref"
         *     data-sku="419160"
         * >
         *
         * On recherche volontairement tous les éléments
         * possédant data-sku.
         */
        $nodes = $xpath->query('//*[@data-sku]');

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if (!$node instanceof DOMElement) {
                    continue;
                }

                $sku = trim(
                    $node->getAttribute('data-sku')
                );

                /*
                 * Comparaison EXACTE.
                 *
                 * 419160  → accepté
                 * 419160A → refusé
                 * 1419160 → refusé
                 */
                if ($sku !== $reference) {
                    continue;
                }

                /*
                 * =================================================
                 * 5. REMONTER JUSQU'À LA CARTE PRODUIT
                 * =================================================
                 *
                 * Exemple :
                 *
                 * div.card-product
                 *   └── ...
                 *       └── span[data-sku]
                 */
                $card = $this->findProductCard(
                    $node
                );

                if ($card === null) {
                    continue;
                }

                /*
                 * =================================================
                 * 6. RECHERCHER LE LIEN DE LA FICHE
                 * =================================================
                 */
                $url = $this->findProductLink(
                    $xpath,
                    $card,
                    $reference
                );

                if ($url !== null) {
                    return $url;
                }
            }
        }

        /*
         * =========================================================
         * 7. FALLBACK GLOBAL
         * =========================================================
         *
         * Si Legrand modifie la structure de :
         *
         * card-product
         *
         * on recherche directement les liens du catalogue.
         */
        return $this->findProductLinkGlobally(
            $xpath,
            $reference
        );
    }

    /**
     * Recherche la carte produit correspondant
     * au nœud data-sku.
     */
    private function findProductCard(DOMNode $node): ?DOMElement
    {
        $current = $node;

        /*
         * On limite la remontée pour éviter de parcourir
         * inutilement tout le DOM.
         */
        for ($i = 0; $i < 12 && $current !== null; $i++) {
            if (
                $current instanceof DOMElement
                && $this->hasCssClass(
                    $current,
                    'card-product'
                )
            ) {
                return $current;
            }

            $current = $current->parentNode;
        }

        return null;
    }

    /**
     * Recherche un lien produit dans une carte.
     */
    private function findProductLink(DOMXPath $xpath, DOMElement $card, string $reference ): ?string
    {
        $links = $xpath->query(
            './/a[@href]',
            $card
        );

        if ($links === false) {
            return null;
        }

        foreach ($links as $link) {
            if (!$link instanceof DOMElement) {
                continue;
            }

            $href = trim(
                $link->getAttribute('href')
            );

            if ($href === '') {
                continue;
            }

            /*
             * Le lien doit être une fiche catalogue.
             */
            if (
                stripos(
                    $href,
                    '/fr/catalogue/'
                ) === false
            ) {
                continue;
            }

            /*
             * La référence doit être présente dans l'URL
             * comme segment indépendant.
             *
             * Exemple accepté :
             *
             * /fr/catalogue/...-419160
             *
             * Exemple refusé :
             *
             * /fr/catalogue/...-1419160
             */
            if (
                !$this->referenceExistsInUrl(
                    $href,
                    $reference
                )
            ) {
                continue;
            }

            return LegrandUrl::absolute(
                $href
            );
        }

        return null;
    }

    /**
     * Fallback global :
     *
     * recherche directement dans tous les liens
     * de la page de résultats.
     */
    private function findProductLinkGlobally( DOMXPath $xpath,  string   $reference ): ?string
    {
        $links = $xpath->query(
            '//a[@href]'
        );

        if ($links === false) {
            return null;
        }

        foreach ($links as $link) {
            if (!$link instanceof DOMElement) {
                continue;
            }

            $href = trim(
                $link->getAttribute('href')
            );

            if ($href === '') {
                continue;
            }

            /*
             * Uniquement les fiches catalogue françaises.
             */
            if (
                stripos(
                    $href,
                    '/fr/catalogue/'
                ) === false
            ) {
                continue;
            }

            if (
                !$this->referenceExistsInUrl(
                    $href,
                    $reference
                )
            ) {
                continue;
            }

            return LegrandUrl::absolute(
                $href
            );
        }

        return null;
    }

    /**
     * Vérifie que la référence apparaît dans l'URL
     * sans accepter une référence contenant celle recherchée.
     *
     * Exemple :
     *
     * 419160
     *      ↓
     * accepté : ...-419160
     *
     * refusé : ...-1419160
     * refusé : ...-419160A
     */
    private function referenceExistsInUrl( string $url,  string $reference ): bool
    {
        return preg_match(
                '/(?:^|[-_\/])'
                . preg_quote($reference, '/')
                . '(?:$|[-_\/?#])/i',
                $url
            ) === 1;
    }

    /**
     * Vérifie la présence exacte d'une classe CSS.
     */
    private function hasCssClass( DOMElement $element, string $class ): bool
    {
        $classes = preg_split(
            '/\s+/',
            trim(
                $element->getAttribute('class')
            )
        );

        if (!is_array($classes)) {
            return false;
        }

        return in_array(
            $class,
            $classes,
            true
        );
    }
}
