<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

use GeekCo\MaxPhpClient\Dto\WebAppContact;
use GeekCo\MaxPhpClient\Internal\HashMatcher;

/**
 * Проверка подлинности контакта, полученного мини-приложением MAX
 * через window.WebApp.requestContact().
 *
 * Это другой механизм, чем контакт из кнопки request_contact в чате
 * (там vcf_info и HMAC-SHA256(access_token, vcf_info)). Здесь сервер
 * подписывает три поля payload'а key=value в алфавитном порядке ключей,
 * склеенных через \n (authDate, phone, userId), а phone идёт без «+»:
 *
 *   hash = HMAC-SHA256("authDate=<v>\nphone=<v>\nuserId=<v>", bot_token)
 *
 * Хэш приходит в hex или base64 (стандартной и URL-safe, с паддингом и без).
 * Сравнение константное, телефон и payload не логируются (OWASP A09).
 */
final class WebAppContactVerifier
{
    public function __construct(
        private readonly string $accessToken,
        private readonly int $maxAge = 0,
    ) {
    }

    /**
     * Верификация подписи; телефон и значения в ответ не возвращаются.
     */
    public function verify(string $authDate, string $phone, string $userId, string $hash): bool
    {
        return $this->resolve($authDate, $phone, $userId, $hash) !== null;
    }

    /**
     * Верификация + подтверждённый контакт (номер в цифрах без «+»).
     * При maxAge > 0 дополнительно проверяется свежесть authDate (replay-защита).
     */
    public function resolve(string $authDate, string $phone, string $userId, string $hash): ?WebAppContact
    {
        $normalized = preg_replace('/\D+/u', '', $phone);

        if (
            $authDate === ''
            || ! is_string($normalized)
            || $normalized === ''
            || $userId === ''
            || $hash === ''
        ) {
            return null;
        }

        if (! $this->isFresh($authDate)) {
            return null;
        }

        $payload = 'authDate=' . $authDate . "\nphone=" . $normalized . "\nuserId=" . $userId;

        $expected = hash_hmac('sha256', $payload, $this->accessToken, true);

        if (! HashMatcher::matches($expected, $hash)) {
            return null;
        }

        return new WebAppContact(
            userId: (int) $userId,
            authDate: (int) $authDate,
            phone: $normalized,
        );
    }

    private function isFresh(string $authDate): bool
    {
        if ($this->maxAge <= 0) {
            return true;
        }

        $value = (int) $authDate;

        if ($value <= 0) {
            return false;
        }

        return time() - $value <= $this->maxAge;
    }
}
