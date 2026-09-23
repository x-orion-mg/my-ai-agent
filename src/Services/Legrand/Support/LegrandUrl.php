<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Support;

final class LegrandUrl
{
    public const SITE = 'https://www.legrand.mg';

    public const SEARCH =
        'https://www.legrand.mg/fr/search';

    public const PIM =
        'https://assets.legrand.com/pim/PHOTOS-WEB/LEGRAND/';

    private function __construct()
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
}