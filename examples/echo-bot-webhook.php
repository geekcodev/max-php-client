<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Exception\MaxApiException;
use GeekCo\MaxPhpClient\Webhook\WebhookHandler;
use GuzzleHttp\Psr7\ServerRequest;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/echo-handler.php';

/**
 * Вебхук-бот. Запуск через PHP:
 *
 *   MAX_API_TOKEN=<token> MAX_WEBHOOK_SECRET=<secret> \
 *     php -S 0.0.0.0:8080 examples/echo-bot-webhook.php
 *
 * или через Docker (см. README):
 *
 *   ./examples/run.sh echo-bot-webhook.php
 *
 * Эндпоинт должен быть доступен по HTTPS (требование API к вебхукам) и
 * отвечать HTTP 200 в течение 30 секунд. Создание подписки:
 *   $client->createSubscription(
 *       url: 'https://example.com/webhook',
 *       updateTypes: ['message_created', 'message_callback'],
 *       secret: 'my-secret',
 *   );
 */

$handler = new WebhookHandler(secret: getenv('MAX_WEBHOOK_SECRET') ?: null);
$client = max_client();

$request = new ServerRequest(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
    getallheaders(),
    file_get_contents('php://input'),
);

if (!$handler->verify($request)) {
    http_response_code(401);
    exit;
}

try {
    $updates = $handler->decode($request);
} catch (MaxApiException) {
    http_response_code(400);
    exit;
}

// decode() возвращает Update (одиночный) или list<Update>.
$updates = $updates instanceof Update ? [$updates] : $updates;

foreach ($updates as $update) {
    max_echo_handle_update($client, $update);
}

http_response_code(200);
