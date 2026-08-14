<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\LongPolling;

use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Exception\MaxApiException;

final class LongPollingRunner
{
    /**
     * @param callable(Update): bool $handler Returns false to stop the loop.
     */
    public function __construct(
        private readonly ApiClient $api,
        private readonly mixed $handler,
        private readonly int $limit = 100,
        private readonly int $timeout = 30,
        private readonly bool $breakOnFailure = true,
    ) {
    }

    /**
     * @param int|null $marker Last processed marker to continue from.
     */
    public function run(?int $marker = null): int
    {
        while (true) {
            try {
                $batch = $this->api->getUpdatesBatch($this->limit, $this->timeout, $marker);
            } catch (MaxApiException $e) {
                if ($this->breakOnFailure) {
                    throw $e;
                }
                sleep(1);
                continue;
            }

            foreach ($batch->updates as $update) {
                if (!($this->handler)($update)) {
                    return $batch->marker ?? $marker ?? 0;
                }
            }

            $marker = $batch->marker ?? $marker;
        }
    }
}
