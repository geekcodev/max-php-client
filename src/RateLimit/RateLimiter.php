<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\RateLimit;

use GeekCo\MaxPhpClient\Exception\RateLimitException;

class RateLimiter
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
        $retryAfter = $this->consume($chatId);

        if ($retryAfter > 0.0) {
            throw new RateLimitException(429, retryAfter: (int) ceil($retryAfter));
        }
    }

    public function wait(int $chatId): void
    {
        $retryAfter = $this->consume($chatId);

        if ($retryAfter > 0.0) {
            $this->sleep($retryAfter);
        }
    }

    protected function sleep(float $seconds): void
    {
        usleep((int) ceil($seconds * 1_000_000));
    }

    /**
     * @return float Seconds until the next token is available (0.0 when consumed).
     */
    private function consume(int $chatId): float
    {
        $now = microtime(true);
        $bucket = $this->buckets[$chatId] ?? null;

        if ($bucket === null) {
            $this->buckets[$chatId] = ['tokens' => $this->maxTokens - 1.0, 'time' => $now];

            return 0.0;
        }

        $tokens = min($this->maxTokens, $bucket['tokens'] + ($now - $bucket['time']) * $this->tokensPerSecond);

        if ($tokens < 1.0) {
            $this->buckets[$chatId] = ['tokens' => $tokens, 'time' => $now];

            return (1.0 - $tokens) / $this->tokensPerSecond;
        }

        $this->buckets[$chatId] = ['tokens' => $tokens - 1.0, 'time' => $now];

        return 0.0;
    }
}
