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
                $updates = $this->api->getUpdates($this->limit, $this->timeout, $marker);
            } catch (MaxApiException $e) {
                if ($this->breakOnFailure) {
                    throw $e;
                }
                sleep(1);
                continue;
            }

            foreach ($updates as $update) {
                $marker = max($marker ?? 0, $update->timestamp);

                if (!($this->handler)($update)) {
                    return $marker;
                }
            }
        }
    }
}
