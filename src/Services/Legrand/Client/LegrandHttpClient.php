<?php

declare(strict_types=1);

namespace MyAIAgent\Services\Legrand\Client;

use  MyAIAgent\Services\Legrand\Exception\LegrandHttpException;

final class LegrandHttpClient
{
    private const USER_AGENT =
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
        . ' MyAIAgent\Services\Legrand\Client\LeWebKit/537.36 (KHTML, like Gecko) '
        . 'Chrome/140.0.0.0 Safari/537.36';

    public function get(string $url): string
    {
        $curl = curl_init();

        if ($curl === false) {
            throw new LegrandHttpException(
                'Impossible d\'initialiser cURL.'
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,

            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,

            CURLOPT_ENCODING => '',

            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,

            CURLOPT_USERAGENT => self::USER_AGENT,

            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Upgrade-Insecure-Requests: 1',
            ],

            CURLOPT_COOKIEFILE => '',
            CURLOPT_COOKIEJAR => '',

            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $html = curl_exec($curl);

        $httpCode = (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        $error = curl_error($curl);

        curl_close($curl);

        if ($html === false) {
            throw new LegrandHttpException(
                'Erreur CURL : ' . $error
            );
        }

        if ($httpCode < 200 || $httpCode >= 400) {
            throw new LegrandHttpException(
                sprintf(
                    'Le site Legrand a retourné HTTP %d pour %s',
                    $httpCode,
                    $url
                )
            );
        }

        return $html;
    }

    public function exists(string $url): bool
    {
        $curl = curl_init($url);

        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,

            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 15,

            CURLOPT_USERAGENT => self::USER_AGENT,

            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        curl_exec($curl);

        $status = (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        curl_close($curl);

        return $status >= 200 && $status < 400;
    }
}