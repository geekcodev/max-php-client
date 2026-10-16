<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

use GeekCo\MaxPhpClient\Internal\HashMatcher;

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

        return HashMatcher::matches($expected, $hash);
    }
}
