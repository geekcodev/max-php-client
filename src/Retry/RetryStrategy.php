<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Retry;

use GeekCo\MaxPhpClient\Exception\ApiException;
use GeekCo\MaxPhpClient\Exception\AttachmentNotReadyException;
use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\Exception\RateLimitException;

final class RetryStrategy
{
    public const IDEMPOTENT_METHODS = ['GET', 'PUT', 'DELETE'];

    /**
     * @param (callable(\Throwable, string): bool)|null $customShouldRetry Retry predicate (exception, method).
     */
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly float $baseDelaySeconds = 1.0,
        public readonly float $maxDelaySeconds = 30.0,
        public readonly float $factor = 2.0,
        public readonly bool $retryOnNonIdempotent = false,
        public readonly mixed $customShouldRetry = null,
    ) {
    }

    public function shouldRetry(\Throwable $exception, string $method): bool
    {
        if ($this->customShouldRetry !== null) {
            return (bool) ($this->customShouldRetry)($exception, $method);
        }

        $safeToRetry = $this->isRetryable($exception);

        if ($exception instanceof AttachmentNotReadyException) {
            return true;
        }

        if (!in_array($method, self::IDEMPOTENT_METHODS, true) && !$this->retryOnNonIdempotent) {
            return false;
        }

        return $safeToRetry;
    }

    private function isRetryable(\Throwable $exception): bool
    {
        if ($exception instanceof NetworkException || $exception instanceof RateLimitException) {
            return true;
        }

        if ($exception instanceof ApiException && $exception->statusCode === 503) {
            return true;
        }

        return false;
    }

    public function delayForAttempt(int $attempt): float
    {
        $delay = $this->baseDelaySeconds * ($this->factor ** $attempt);

        return min($delay, $this->maxDelaySeconds);
    }
}
