<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Support;

final class LegrandReference
{
    private function __construct()
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
}