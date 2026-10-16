<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Security\WebAppContactVerifier;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebAppContactVerifierTest extends TestCase
{
    private const TOKEN = '123456:test-bot-token';

    private const USER_ID = 67890;

    private const PHONE = '79991234567';

    /**
     * Подпись, как её выдаёт window.WebApp.requestContact(): payload key=value
     * в алфавитном порядке ключей, склеенный через \n, phone без «+».
     */
    private function sign(int $authDate, string $phone = self::PHONE, int $userId = self::USER_ID, string $encoding = 'hex'): string
    {
        $payload = 'authDate=' . $authDate . "\nphone=" . $phone . "\nuserId=" . $userId;
        $hash = hash_hmac('sha256', $payload, self::TOKEN, true);

        return match ($encoding) {
            'base64' => base64_encode($hash),
            'base64url' => rtrim(strtr(base64_encode($hash), '+/', '-_'), '='),
            default => bin2hex($hash),
        };
    }

    #[Test]
    public function it_accepts_hex_signature(): void
    {
        $authDate = time();

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $this->assertTrue($verifier->verify((string) $authDate, self::PHONE, (string) self::USER_ID, $this->sign($authDate)));
    }

    #[Test]
    public function it_accepts_standard_and_url_safe_base64(): void
    {
        $authDate = time();

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $this->assertTrue($verifier->verify(
            (string) $authDate,
            self::PHONE,
            (string) self::USER_ID,
            $this->sign($authDate, encoding: 'base64'),
        ));
        $this->assertTrue($verifier->verify(
            (string) $authDate,
            self::PHONE,
            (string) self::USER_ID,
            $this->sign($authDate, encoding: 'base64url'),
        ));
    }

    #[Test]
    public function it_resolves_the_contact_with_normalized_phone(): void
    {
        $authDate = time();

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $contact = $verifier->resolve(
            (string) $authDate,
            '+7 999 123-45-67',
            (string) self::USER_ID,
            $this->sign($authDate),
        );

        $this->assertNotNull($contact);
        $this->assertSame(self::PHONE, $contact->phone);
        $this->assertSame($authDate, $contact->authDate);
        $this->assertSame(self::USER_ID, $contact->userId);
        $this->assertSame(
            ['user_id' => self::USER_ID, 'auth_date' => $authDate, 'phone' => self::PHONE],
            $contact->toArray(),
        );
    }

    #[Test]
    public function it_rejects_a_tampered_payload(): void
    {
        $authDate = time();

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $this->assertNull($verifier->resolve(
            (string) $authDate,
            self::PHONE,
            (string) (self::USER_ID + 1),
            $this->sign($authDate),
        ));
    }

    #[Test]
    public function it_rejects_a_stale_auth_date_when_max_age_is_set(): void
    {
        $authDate = time() - 90000;

        $verifier = new WebAppContactVerifier(self::TOKEN, maxAge: 86400);

        $this->assertNull($verifier->resolve((string) $authDate, self::PHONE, (string) self::USER_ID, $this->sign($authDate)));
    }

    #[Test]
    public function it_skips_freshness_check_when_max_age_is_zero(): void
    {
        $authDate = time() - 90000;

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $this->assertNotNull($verifier->resolve((string) $authDate, self::PHONE, (string) self::USER_ID, $this->sign($authDate)));
    }

    #[Test]
    public function it_rejects_a_non_positive_auth_date_when_max_age_is_set(): void
    {
        $verifier = new WebAppContactVerifier(self::TOKEN, maxAge: 86400);

        $this->assertNull($verifier->resolve('0', self::PHONE, (string) self::USER_ID, $this->sign(0)));
    }

    #[Test]
    public function it_rejects_empty_inputs(): void
    {
        $authDate = time();

        $verifier = new WebAppContactVerifier(self::TOKEN);

        $this->assertNull($verifier->resolve('', self::PHONE, (string) self::USER_ID, $this->sign($authDate)));
        $this->assertNull($verifier->resolve((string) $authDate, 'abc', (string) self::USER_ID, $this->sign($authDate)));
        $this->assertNull($verifier->resolve((string) $authDate, self::PHONE, '', $this->sign($authDate)));
        $this->assertNull($verifier->resolve((string) $authDate, self::PHONE, (string) self::USER_ID, ''));
    }
}
