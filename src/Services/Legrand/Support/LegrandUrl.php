<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use MyAIAgent\Services\Legrand\Client\LegrandHttpClient;

final class LegrandUrl
{
    public const string SITE = 'https://www.legrand.mg';

    public const string SEARCH = 'https://www.legrand.mg/fr/search';

    public const string PIM = 'https://assets.legrand.com/pim/PHOTOS-WEB/LEGRAND/';

    public const string LEGRAND_DOMAIN = 'https://www.legrand.com';

    public const string LEGRAND_PIM = 'https://assets.legrand.com/pim/PHOTOS-WEB/LEGRAND/';

    public const string LEGRAND_ECAT = 'https://www.legrand.com/ecatalogue/en';

    public function __construct(
        private readonly LegrandHttpClient $http
    )
    {
    }

    public static function absolute(string $url): string
    {
        $url = html_entity_decode(
            trim($url),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }

        if (str_starts_with($url, '/')) {
            return self::SITE . $url;
        }

        return self::SITE . '/' . $url;
    }

    /**
     * TROUVER LA PAGE PRODUIT DANS LES RESULTATS PAGE LEGRAND.COM
     */

    public function findLegrandProductPage(string $reference): ?string
    {
        if ($reference === '') {
            return null;
        }

        /*
         * Recherche officielle Legrand.
         */
        $searchUrl = LegrandUrl::LEGRAND_ECAT .
            '/search?search=' .
            urlencode($reference);

        try {
            $html = $this->http->getEn($searchUrl);
        } catch (\Throwable) {
            return null;
        }

        if ($html === '') {
            return null;
        }


        /*
         * --------------------------------------------------------
         * DOM
         * --------------------------------------------------------
         */

        libxml_use_internal_errors(true);


        $dom = new DOMDocument();

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOWARNING |
            LIBXML_NOERROR |
            LIBXML_NONET
        );


        if (!$loaded) {
            return null;
        }


        $xpath = new DOMXPath($dom);


        /*
         * --------------------------------------------------------
         * Recherche exacte du data-sku
         * --------------------------------------------------------
         */

        $nodes = $xpath->query(
            '//*[@data-sku="' .
            $reference .
            '"]'
        );


        foreach ($nodes as $node) {


            /*
             * On remonte jusqu'à la carte produit.
             *
             * Exemple :
             *
             * <div class="card-product">
             *     ...
             *     data-sku="419160"
             *     ...
             *     <a href="...">
             */

            $card = $node;


            for ($i = 0; $i < 8 && $card; $i++) {

                if (
                    $card instanceof DOMElement &&
                    preg_match(
                        '/\bcard-product\b/i',
                        $card->getAttribute('class')
                    )
                ) {
                    break;
                }


                $card = $card->parentNode;
            }


            if (!$card) {
                continue;
            }


            /*
             * Recherche des liens produits.
             */
            $links = $xpath->query(
                './/a[@href]',
                $card
            );


            foreach ($links as $link) {

                $href = trim(
                    $link->getAttribute('href')
                );


                if (
                    stripos(
                        $href,
                        '/ecatalogue/en/catalog/products/'
                    ) === false
                ) {
                    continue;
                }


                /*
                 * Le slug doit également contenir
                 * la référence.
                 */
                if (
                    stripos(
                        $href,
                        $reference
                    ) === false
                ) {
                    continue;
                }


                return $this->legrandAbsoluteUrl(
                    $href
                );
            }
        }


        /*
         * --------------------------------------------------------
         * FALLBACK REGEX
         * --------------------------------------------------------
         *
         * Si la structure HTML change légèrement.
         */

        $pattern =
            '#href=["\']'
            . '([^"\']*/ecatalogue/en/catalog/products/'
            . '[^"\']*'
            . preg_quote($reference, '#')
            . '[^"\']*)'
            . '["\']#i';


        if (
            preg_match(
                $pattern,
                $html,
                $match
            )
        ) {

            return $this->legrandAbsoluteUrl(
                html_entity_decode(
                    $match[1]
                )
            );
        }


        return null;
    }

    private function legrandAbsoluteUrl(string $url): string
    {
        $url = html_entity_decode(
            trim($url),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );


        if ($url === '') {
            return '';
        }


        /*
         * URL absolue
         */
        if (preg_match(
            '#^https?://#i',
            $url
        )) {
            return $url;
        }


        /*
         * //assets...
         */
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }


        /*
         * URL relative
         */
        if (str_starts_with($url, '/')) {
            return LegrandUrl::LEGRAND_DOMAIN . $url;
        }


        return LegrandUrl::LEGRAND_DOMAIN . '/' . $url;
    }
}