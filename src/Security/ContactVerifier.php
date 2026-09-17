<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

final class ContactVerifier
{
    public function __construct(
        private readonly string $accessToken,
    ) {
    }

    /**
     * Проверка подлинности контакта из кнопки request_contact.
     *
     * Hash = HMAC-SHA256(access_token, vcf_info). MAX подписывает vcf_info
     * как есть — сырые байты с реальными CRLF (подтверждено продакшеном:
     * проф. vCard с CRLF и концевым переводом строки). Если транспорт JSON
     * оставил литеральные '\r\n' (двойное экранирование), они восстанавливаются
     * в реальные CRLF. Хэш приходит в hex или base64 (стандартной и URL-safe,
     * с паддингом и без). Сравнение константное.
     */
    public function verify(string $vcfInfo, string $hash): bool
    {
        if ($vcfInfo === '' || $hash === '') {
            return false;
        }

        if ($this->matches($vcfInfo, $hash)) {
            return true;
        }

        $restored = str_replace('\\r\\n', "\r\n", $vcfInfo);

        if ($restored === $vcfInfo) {
            return false;
        }

        return $this->matches($restored, $hash);
    }

    private function matches(string $vcfInfo, string $hash): bool
    {
        $expected = hash_hmac('sha256', $vcfInfo, $this->accessToken, true);

        if ($this->matchesHex($expected, $hash)) {
            return true;
        }

        foreach ($this->base64Variants($hash) as $variant) {
            if (hash_equals($expected, $variant)) {
                return true;
            }
        }

        return false;
    }

    private function matchesHex(string $expected, string $hash): bool
    {
        if (strlen($hash) !== 64 || ! ctype_xdigit($hash)) {
            return false;
        }

        $decoded = hex2bin($hash);

        return $decoded !== false && hash_equals($expected, $decoded);
    }

    /**
     * Декодирование base64: стандартный алфавит и URL-safe, с паддингом и без.
     *
     * @return list<string> непустые кандидаты (только успешно декодированные)
     */
    private function base64Variants(string $hash): array
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
