<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Extractor;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ProductTechnicalDataExtractor
{
    /**
     * @return array<string, string>
     */
    public function extract(string $html): array
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOWARNING |
            LIBXML_NOERROR |
            LIBXML_NONET
        );

        if (!$loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);

        $data = [];

        /*
         * ======================================================
         * 1. TABLES
         * ======================================================
         */
        $tables = $xpath->query('//table');

        if ($tables !== false) {
            foreach ($tables as $table) {
                if (!$table instanceof DOMElement) {
                    continue;
                }

                $rows = $xpath->query(
                    './/tr',
                    $table
                );

                if ($rows === false) {
                    continue;
                }

                foreach ($rows as $row) {
                    $cells = $xpath->query(
                        './th|./td',
                        $row
                    );

                    if (
                        $cells === false
                        || $cells->length < 2
                    ) {
                        continue;
                    }

                    $key = trim(
                        $cells->item(0)?->textContent ?? ''
                    );

                    $value = trim(
                        $cells->item(1)?->textContent ?? ''
                    );

                    if ($key === '' || $value === '') {
                        continue;
                    }

                    $data[$this->clean($key)]
                        = $this->clean($value);
                }
            }
        }

        /*
         * ======================================================
         * 2. BLOCS DE CARACTÉRISTIQUES
         * ======================================================
         *
         * Fallback pour les structures :
         *
         * <div>
         *   <span>Courant nominal</span>
         *   <span>16 A</span>
         * </div>
         */
        $possibleRows = $xpath->query(
            '//*[contains(
                translate(
                    @class,
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                    "abcdefghijklmnopqrstuvwxyz"
                ),
                "technical"
            )]'
        );

        if ($possibleRows !== false) {
            foreach ($possibleRows as $element) {
                if (!$element instanceof DOMElement) {
                    continue;
                }

                $children = [];

                foreach ($element->childNodes as $child) {
                    if (
                        $child instanceof DOMElement
                    ) {
                        $text = $this->clean(
                            $child->textContent
                        );

                        if ($text !== '') {
                            $children[] = $text;
                        }
                    }
                }

                if (count($children) >= 2) {
                    $key = array_shift($children);

                    if ($key !== null) {
                        $data[$key] = implode(
                            ' ',
                            $children
                        );
                    }
                }
            }
        }

        return $data;
    }

    private function clean(string $value): string
    {
        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                html_entity_decode(
                    $value,
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
            ) ?? ''
        );
    }
}