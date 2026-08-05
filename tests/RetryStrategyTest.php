<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Exception\AttachmentNotReadyException;
use GeekCo\MaxPhpClient\Exception\ApiException;
use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\Exception\RateLimitException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RetryStrategyTest extends TestCase
{
    #[Test]
    public function it_calculates_exponential_delays(): void
    {
        $strategy = new RetryStrategy(baseDelaySeconds: 1.0, factor: 2.0, maxDelaySeconds: 30.0);

        $this->assertSame(1.0, $strategy->delayForAttempt(0));
        $this->assertSame(2.0, $strategy->delayForAttempt(1));
        $this->assertSame(4.0, $strategy->delayForAttempt(2));
    }

    #[Test]
    public function it_caps_the_delay(): void
    {
        $strategy = new RetryStrategy(baseDelaySeconds: 10.0, factor: 4.0, maxDelaySeconds: 30.0);

        $this->assertSame(30.0, $strategy->delayForAttempt(1));
    }

    #[Test]
    public function it_retries_network_and_rate_limit_errors_on_idempotent_methods(): void
    {
        $strategy = new RetryStrategy();

        $this->assertTrue($strategy->shouldRetry(new NetworkException('x'), 'GET'));
        $this->assertTrue($strategy->shouldRetry(new RateLimitException(429), 'GET'));
        $this->assertTrue($strategy->shouldRetry(new ApiException(503), 'DELETE'));
    }

    #[Test]
    public function it_does_not_retry_non_idempotent_methods_by_default(): void
    {
        $strategy = new RetryStrategy();

        $this->assertFalse($strategy->shouldRetry(new NetworkException('x'), 'POST'));
        $this->assertFalse($strategy->shouldRetry(new RateLimitException(429), 'PATCH'));
    }

    #[Test]
    public function it_always_retries_attachment_not_ready(): void
    {
        $strategy = new RetryStrategy();

        $this->assertTrue($strategy->shouldRetry(new AttachmentNotReadyException(400), 'POST'));
    }

    #[Test]
    public function it_uses_the_custom_predicate_when_provided(): void
    {
        $strategy = new RetryStrategy(customShouldRetry: static fn ($e, $method): bool => $method === 'POST');

        $this->assertTrue($strategy->shouldRetry(new ApiException(400), 'POST'));
        $this->assertFalse($strategy->shouldRetry(new ApiException(400), 'GET'));
    }
}
