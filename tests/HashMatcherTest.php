<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Internal\HashMatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HashMatcherTest extends TestCase
{
    private function expected(): string
    {
        return hash_hmac('sha256', 'payload', 'secret', true);
    }

    #[Test]
    public function it_matches_hex(): void
    {
        $this->assertTrue(HashMatcher::matches($this->expected(), bin2hex($this->expected())));
        $this->assertTrue(HashMatcher::matches($this->expected(), strtoupper(bin2hex($this->expected()))));
    }

    #[Test]
    public function it_matches_standard_base64(): void
    {
        $this->assertTrue(HashMatcher::matches($this->expected(), base64_encode($this->expected())));
        $this->assertTrue(HashMatcher::matches($this->expected(), rtrim(base64_encode($this->expected()), '=')));
    }

    #[Test]
    public function it_matches_url_safe_base64(): void
    {
        $urlSafe = strtr(base64_encode($this->expected()), '+/', '-_');

        $this->assertTrue(HashMatcher::matches($this->expected(), $urlSafe));
        $this->assertTrue(HashMatcher::matches($this->expected(), rtrim($urlSafe, '=')));
    }

    #[Test]
    public function it_rejects_a_wrong_or_garbage_hash(): void
    {
        $this->assertFalse(HashMatcher::matches($this->expected(), hash_hmac('sha256', 'other', 'secret')));
        $this->assertFalse(HashMatcher::matches($this->expected(), '!!!not-a-hash!!!'));
        $this->assertFalse(HashMatcher::matches($this->expected(), str_repeat('0', 64)));
    }

    #[Test]
    public function it_rejects_an_empty_hash(): void
    {
        $this->assertFalse(HashMatcher::matches($this->expected(), ''));
    }
}
