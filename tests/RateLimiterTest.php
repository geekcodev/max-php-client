<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Exception\RateLimitException;
use GeekCo\MaxPhpClient\RateLimit\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    #[Test]
    public function it_allows_burst_of_max_tokens(): void
    {
        $limiter = new RateLimiter(tokensPerSecond: 2.0, maxTokens: 2.0);

        $limiter->acquire(1);
        $limiter->acquire(1);

        $this->expectException(RateLimitException::class);
        $limiter->acquire(1);
    }

    #[Test]
    public function it_refills_tokens_over_time(): void
    {
        $limiter = new RateLimiter(tokensPerSecond: 10.0, maxTokens: 2.0);

        $limiter->acquire(1);
        $limiter->acquire(1);

        usleep(150_000);

        $limiter->acquire(1);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_tracks_chats_independently(): void
    {
        $limiter = new RateLimiter(tokensPerSecond: 2.0, maxTokens: 2.0);

        $limiter->acquire(1);
        $limiter->acquire(1);

        $limiter->acquire(2);
        $this->addToAssertionCount(1);
    }
}
