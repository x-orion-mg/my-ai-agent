<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Support;

use MyAIAgent\Services\Legrand\Product\LegrandProduct;

final class LegrandReference
{
    public function __construct()
    {
    }

    public static function normalize(string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return '';
        }

        /*
         * REF 419160
         * REF. 419160
         * ref. 419160
         */
        $reference = preg_replace(
            '/^REF\.?\s*/i',
            '',
            $reference
        ) ?? $reference;

        /*
         * Conservation des lettres :
         *
         * 079140L
         * 067xxx
         */
        return strtoupper(
            preg_replace(
                '/[^A-Z0-9]/i',
                '',
                $reference
            ) ?? ''
        );
    }

    public function buildProductFromSpider(
        array $article,
        string $reference
    ): LegrandProduct {
        $image = $article['articleImages'][0]['filepath'] ?? null;

        $technicalData = $this->buildTechnicalDataFromSpider(
            $article
        );

        return new LegrandProduct(
            reference: $reference,
            url: $article['brand']['link'] ?? null,
            image: $this->getSpiderImage($image),
            technicalData: $technicalData
        );
    }

    private function buildTechnicalDataFromSpider(array $article): ?string
    {
        $technicalDetails = $article['technicalDetails'] ?? [];

        if (!is_array($technicalDetails) || empty($technicalDetails)) {
            return null;
        }

        $html = '<table>';

        foreach ($technicalDetails as $detail) {
            $label = trim($detail['label'] ?? '');
            $value = trim($detail['value'] ?? '');

            if ($label === '' && $value === '') {
                continue;
            }

            $html .= sprintf(
                '<tr><th>%s</th><td>%s</td></tr>',
                htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }

        $html .= '</table>';

        return $html;
    }
    private function getSpiderImage(
        $filepath
    ): ?string {

        $spiderBaseUrl = LegrandUrl::SPIDER_BASE_URL;

        if ($filepath === '') {
            return null;
        }

        if (
            str_starts_with($filepath, 'http://') ||
            str_starts_with($filepath, 'https://')
        ) {
            return $filepath;
        }

        return rtrim($spiderBaseUrl, '/')
            . '/'
            . ltrim($filepath, '/');
    }
}