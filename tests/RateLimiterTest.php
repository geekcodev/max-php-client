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

    #[Test]
    public function it_waits_instead_of_throwing_when_the_bucket_is_empty(): void
    {
        $limiter = new RateLimiter(tokensPerSecond: 2.0, maxTokens: 2.0);

        $limiter->acquire(1);
        $limiter->acquire(1);

        $start = microtime(true);
        $limiter->wait(1);
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.4, $elapsed);

        $limiter->acquire(1);
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function it_accumulates_sleep_between_wait_calls(): void
    {
        $limiter = new class (2.0, 2.0) extends RateLimiter {
            public float $sleepSeconds = 0.0;

            protected function sleep(float $seconds): void
            {
                $this->sleepSeconds += $seconds;
            }
        };

        $limiter->acquire(1);
        $limiter->acquire(1);
        $limiter->wait(1);
        $limiter->wait(1);

        $this->assertGreaterThan(0.9, $limiter->sleepSeconds);
    }
}
