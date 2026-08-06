<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

/**
 * Серверная валидация стартовых данных мини-приложения MAX.
 *
 * Алгоритм описан в https://dev.max.ru/docs/webapps/validation:
 *   secret_key = HMAC-SHA256(key: "WebAppData", data: bot_token)
 *   signature  = hex(HMAC-SHA256(key: secret_key, data: launch_params))
 *
 * Параметры launch_params: значения из WebAppData после URL-декодирования,
 * отсортированные по ключам a→z и склеенные как key=value через \n, без hash.
 */
final class WebAppDataValidator
{
    private const DATA_KEY = 'WebAppData';

    public function __construct(
        private readonly string $accessToken,
    ) {
    }

    /**
     * Верификация строки window.WebApp.initData (значение параметра WebAppData).
     */
    public function verify(string $webAppData): bool
    {
        $pairs = $this->parsePairs($webAppData);
        if ($pairs === null) {
            return false;
        }

        foreach ($pairs as &$pair) {
            $pair[1] = rawurldecode($pair[1]);
        }
        unset($pair);

        $hashes = [];
        foreach ($pairs as $index => $pair) {
            if ($pair[0] === 'hash') {
                $hashes[] = [$index, $pair[1]];
            }
        }
        if (count($hashes) !== 1) {
            return false;
        }
        [$hashIndex, $originalHash] = $hashes[0];

        $pairs = array_values(array_filter(
            $pairs,
            static fn (array $pair): bool => $pair[0] !== 'hash',
        ));
        usort($pairs, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));

        $launchParams = implode("\n", array_map(
            static fn (array $pair): string => $pair[0] . '=' . $pair[1],
            $pairs,
        ));

        $secretKey = hash_hmac('sha256', $this->accessToken, self::DATA_KEY, binary: true);

        return hash_equals(hash_hmac('sha256', $launchParams, $secretKey), $originalHash);
    }

    /**
     * Верификация данных из URL, по которому открыто мини-приложение.
     * Параметры платформы извлекаются из фрагмента URL (данные после #).
     */
    public function verifyFromUrl(string $url): bool
    {
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if ($fragment === false || $fragment === null) {
            return false;
        }

        $pairs = $this->parsePairs($fragment);
        if ($pairs === null) {
            return false;
        }

        $keys = array_map(static fn (array $pair): string => $pair[0], $pairs);
        if (count($keys) !== count(array_unique($keys))) {
            return false;
        }

        $webAppData = null;
        foreach ($pairs as $pair) {
            if ($pair[0] === self::DATA_KEY) {
                $webAppData = rawurldecode($pair[1]);
                break;
            }
        }

        return $webAppData !== null && $this->verify($webAppData);
    }

    /**
     * @return list<array{string, string}>|null
     */
    private function parsePairs(string $data): ?array
    {
        if ($data === '') {
            return null;
        }

        $pairs = [];
        foreach (explode('&', $data) as $item) {
            $pair = explode('=', $item, 2);
            if (count($pair) !== 2 || $pair[0] === '') {
                return null;
            }
            $pairs[] = [$pair[0], $pair[1]];
        }

        return $pairs;
    }
}
