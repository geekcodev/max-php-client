<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\LongPolling\LongPollingRunner;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/echo-handler.php';

/**
 * Long polling-бот. Запуск:
 *
 *   MAX_API_TOKEN=<token> php examples/echo-bot-long-polling.php
 *
 * Внимание: long polling ограничен по скорости и хранению событий —
 * подходит для разработки и тестирования, но не для production.
 */

$client = max_client();

$runner = new LongPollingRunner(
    api: $client,
    handler: static function (Update $update) use ($client): bool {
        max_echo_handle_update($client, $update);

        return true; // false — остановить цикл
    },
);

$lastMarker = $runner->run();

fwrite(STDOUT, sprintf("Stopped at marker: %d\n", $lastMarker));
