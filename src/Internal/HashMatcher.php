<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Internal;

/**
 * Сравнение двоичного HMAC-хэша с присланным значением в hex или base64
 * (стандартной и URL-safe, с паддингом и без). Общее место для Security-классов,
 * которые проверяют подписи MAX, — устранение дублирования.
 */
final class HashMatcher
{
    public static function matches(string $expected, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        if (self::matchesHex($expected, $hash)) {
            return true;
        }

        foreach (self::base64Variants($hash) as $variant) {
            if (hash_equals($expected, $variant)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesHex(string $expected, string $hash): bool
    {
        if (strlen($hash) !== 64 || ! ctype_xdigit($hash)) {
            return false;
        }

        $decoded = hex2bin($hash);

        return $decoded !== false && hash_equals($expected, $decoded);
    }

    /**
     * @return list<string> успешно декодированные кандидаты (стандартный алфавит и URL-safe)
     */
    private static function base64Variants(string $hash): array
    {
        $variants = [];

        foreach ([$hash, strtr($hash, '-_', '+/')] as $candidate) {
            $decoded = base64_decode($candidate, true);

            if ($decoded !== false) {
                $variants[] = $decoded;
            }
        }

        return $variants;
    }
}
