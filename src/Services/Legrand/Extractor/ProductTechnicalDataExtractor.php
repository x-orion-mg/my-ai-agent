<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Extractor;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ProductTechnicalDataExtractor
{
    public function __construct(
    )
    {
    }
    /**
     * Retourne uniquement les tableaux de caractéristiques techniques
     * sous forme de HTML.
     */
    public function extract(string $html): string
    {
        $dom = new DOMDocument();

        libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $html,
            LIBXML_NOWARNING |
            LIBXML_NOERROR |
            LIBXML_NONET
        );

        libxml_clear_errors();

        if (!$loaded) {
            return '';
        }

        $xpath = new DOMXPath($dom);

        $tables = $xpath->query('//table');

        if ($tables === false || $tables->length === 0) {
            return '';
        }

        $result = '';

        foreach ($tables as $table) {
            if (!$table instanceof DOMElement) {
                continue;
            }

            $result .= $dom->saveHTML($table);
        }

        return $result;
    }
}