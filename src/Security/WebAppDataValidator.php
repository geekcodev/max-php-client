<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

use GeekCo\MaxPhpClient\Dto\WebAppIdentity;

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
        private readonly int $maxAge = 0,
    ) {
    }

    /**
     * Верификация строки window.WebApp.initData (значение параметра WebAppData).
     */
    public function verify(string $webAppData): bool
    {
        return $this->decodeAndVerify($webAppData) !== null;
    }

    /**
     * Верификация данных из URL, по которому открыто мини-приложение.
     * WebAppData ищется в query-параметре (?WebAppData=...) или во фрагменте (#WebAppData=...).
     */
    public function verifyFromUrl(string $url): bool
    {
        $webAppData = $this->webAppDataFromUrl($url);

        return $webAppData !== null && $this->verify($webAppData);
    }

    /**
     * Верификация + извлечение идентификации пользователя и диалога.
     * При maxAge > 0 дополнительно проверяется свежесть auth_date (replay-защита).
     */
    public function resolve(string $webAppData): ?WebAppIdentity
    {
        $pairs = $this->decodeAndVerify($webAppData);
        if ($pairs === null) {
            return null;
        }

        if ($this->maxAge > 0 && !$this->isFresh($pairs)) {
            return null;
        }

        return new WebAppIdentity(
            userId: $this->idFromJson($pairs['user'] ?? null),
            chatId: $this->idFromJson($pairs['chat'] ?? null),
        );
    }

    /**
     * resolve() из URL (query-параметр или фрагмент).
     */
    public function resolveFromUrl(string $url): ?WebAppIdentity
    {
        $webAppData = $this->webAppDataFromUrl($url);
        if ($webAppData === null) {
            return null;
        }

        return $this->resolve($webAppData);
    }

    /**
     * Возвращает декодированные пары (без hash) при валидной подписи, иначе null.
     *
     * @return array<string, string>|null
     */
    private function decodeAndVerify(string $webAppData): ?array
    {
        $pairs = $this->parsePairs($webAppData);
        if ($pairs === null) {
            return null;
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
            return null;
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

        if (!hash_equals(hash_hmac('sha256', $launchParams, $secretKey), $originalHash)) {
            return null;
        }

        $result = [];
        foreach ($pairs as $pair) {
            $result[$pair[0]] = $pair[1];
        }

        return $result;
    }

    private function webAppDataFromUrl(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        foreach ([$parts['fragment'] ?? null, $parts['query'] ?? null] as $source) {
            if ($source === null) {
                continue;
            }

            $pairs = $this->parsePairs($source);
            if ($pairs === null) {
                return null;
            }

            $keys = array_map(static fn (array $pair): string => $pair[0], $pairs);
            if (count($keys) !== count(array_unique($keys))) {
                return null;
            }

            foreach ($pairs as $pair) {
                if ($pair[0] === self::DATA_KEY) {
                    $webAppData = rawurldecode($pair[1]);
                    if ($webAppData !== '') {
                        return $webAppData;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $pairs
     */
    private function isFresh(array $pairs): bool
    {
        $authDate = isset($pairs['auth_date']) ? (int) $pairs['auth_date'] : 0;

        return $authDate > 0 && time() - $authDate <= $this->maxAge;
    }

    private function idFromJson(?string $json): ?int
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded) || !isset($decoded['id']) || !is_numeric($decoded['id'])) {
            return null;
        }

        return (int) $decoded['id'];
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
