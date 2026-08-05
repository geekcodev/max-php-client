<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\RateLimit;

use GeekCo\MaxPhpClient\Exception\RateLimitException;

final class RateLimiter
{
    /**
     * @var array<int, array{tokens: float, time: float}>
     */
    private array $buckets = [];

    public function __construct(
        public readonly float $tokensPerSecond = 2.0,
        public readonly float $maxTokens = 2.0,
    ) {
    }

    public function acquire(int $chatId): void
    {
        $now = microtime(true);
        $bucket = $this->buckets[$chatId] ?? null;

        if ($bucket === null) {
            $this->buckets[$chatId] = ['tokens' => $this->maxTokens - 1.0, 'time' => $now];

            return;
        }

        $tokens = min($this->maxTokens, $bucket['tokens'] + ($now - $bucket['time']) * $this->tokensPerSecond);

        if ($tokens < 1.0) {
            $this->buckets[$chatId] = ['tokens' => $tokens, 'time' => $now];

            $retryAfter = (int) ceil((1.0 - $tokens) / $this->tokensPerSecond);

            throw new RateLimitException(429, retryAfter: $retryAfter);
        }

        $this->buckets[$chatId] = ['tokens' => $tokens - 1.0, 'time' => $now];
    }
}
