<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Extractor;

use  MyAIAgent\Services\Legrand\Client\LegrandHttpClient;
use  MyAIAgent\Services\Legrand\Support\LegrandReference;
use  MyAIAgent\Services\Legrand\Support\LegrandUrl;
use DOMDocument;
use DOMElement;
use DOMXPath;

final readonly class ProductImageExtractor
{
    public function __construct(
        private LegrandHttpClient $http
    ) {
    }

    public function extract(
        string $html,
        string $reference
    ): ?string {
        $reference = LegrandReference::normalize($reference);

        if ($reference === '') {
            return null;
        }

        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

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
         * PRIORITÉ 1
         *
         * Lien zoom PIM.
         */
        $links = $xpath->query(
            '//a[
                contains(
                    concat(" ", normalize-space(@class), " "),
                    " link-zoom "
                )
                and
                contains(
                    concat(" ", normalize-space(@class), " "),
                    " open-zoom "
                )
            ]'
        );

        if ($links !== false) {
            foreach ($links as $link) {
                if (!$link instanceof DOMElement) {
                    continue;
                }

                $href = trim(
                    $link->getAttribute('href')
                );

                if (
                    stripos(
                        $href,
                        'assets.legrand.com/pim/PHOTOS-WEB/LEGRAND/'
                    ) === false
                ) {
                    continue;
                }

                return LegrandUrl::absolute($href);
            }
        }

        /*
         * PRIORITÉ 2
         *
         * N'importe quel lien PIM.
         */
        $links = $xpath->query('//a[@href]');

        if ($links !== false) {
            foreach ($links as $link) {
                if (!$link instanceof DOMElement) {
                    continue;
                }

                $href = trim(
                    $link->getAttribute('href')
                );

                if (
                    stripos(
                        $href,
                        'assets.legrand.com/pim/PHOTOS-WEB/LEGRAND/'
                    ) !== false
                ) {
                    return LegrandUrl::absolute($href);
                }
            }
        }

        /*
         * PRIORITÉ 3
         *
         * JSON-LD.
         */
        $image = $this->extractFromJsonLd(
            $html,
            $reference
        );

        if ($image !== null) {
            return $image;
        }

        /*
         * PRIORITÉ 4
         *
         * Recherche brute dans le HTML.
         */
        if (
            preg_match(
                '#https?://assets\.legrand\.com/pim/'
                . 'PHOTOS-WEB/LEGRAND/[^"\'>\s]+#i',
                $html,
                $match
            )
        ) {
            return html_entity_decode(
                $match[0],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            );
        }

        return null;
    }

    private function extractFromJsonLd(
        string $html,
        string $reference
    ): ?string {
        preg_match_all(
            '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>'
            . '(.*?)'
            . '</script>#is',
            $html,
            $matches
        );

        foreach ($matches[1] as $json) {
            $data = json_decode(
                trim($json),
                true
            );

            if (!is_array($data)) {
                continue;
            }

            $products = isset($data['@graph'])
            && is_array($data['@graph'])
                ? $data['@graph']
                : [$data];

            foreach ($products as $product) {
                if (!is_array($product)) {
                    continue;
                }

                if (
                    isset($product['sku'])
                    && (string) $product['sku']
                    !== $reference
                ) {
                    continue;
                }

                if (!isset($product['image'])) {
                    continue;
                }

                $image = $product['image'];

                if (
                    is_array($image)
                    && isset($image['url'])
                ) {
                    return (string) $image['url'];
                }

                if (is_string($image)) {
                    return $image;
                }
            }
        }

        return null;
    }

    /**
     * Recherche directe dans le PIM.
     *
     * Utilisée comme fallback.
     */
    public function findDirectPim(
        string $reference
    ): ?string {
        $reference = LegrandReference::normalize(
            $reference
        );

        if ($reference === '') {
            return null;
        }

        $folder = substr($reference, 0, 2);

        $candidates = [
            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '-LEGRAND-1000.jpg',

            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '-LEGRAND-800.jpg',

            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '-LEGRAND-600.jpg',

            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '-LEGRAND-500.jpg',

            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '-LEGRAND.jpg',

            LegrandUrl::PIM
            . $folder . '/'
            . $reference
            . '.jpg',
        ];

        foreach ($candidates as $url) {
            if ($this->http->exists($url)) {
                return $url;
            }
        }

        return null;
    }
}