# MAX PHP Client

[![CI](https://github.com/geekcodev/max-php-client/actions/workflows/ci.yml/badge.svg)](https://github.com/geekcodev/max-php-client/actions/workflows/ci.yml)

Универсальный, production-grade PHP-клиент для [MAX Messenger Bot API](https://max.ru). Framework-agnostic ядро
(PSR-7/PSR-17/PSR-18), которое может быть использовано как основа для модулей интеграции в разные фреймворки (Laravel,
Symfony и др.).

## Требования

- PHP >= 8.4
- PSR-18 HTTP-клиент (например, `guzzlehttp/guzzle`)
- PSR-17 фабрики запросов/стримов/URI

## Установка

```bash
composer require geekcodev/max-php-client
```

## Быстрый старт

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;

$psrFactory = new HttpFactory();

$client = ApiClient::create(
    httpClient: new GuzzleClient(),
    requestFactory: $psrFactory,
    streamFactory: $psrFactory,
    uriFactory: $psrFactory,
    accessToken: getenv('MAX_ACCESS_TOKEN'),
);

$me = $client->getMe();

$message = $client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(text: 'Привет из PHP!'),
);
```

Токен передаётся в заголовке `Authorization` **без** префикса `Bearer`. Передача токена через query-параметры не
поддерживается.

## Возможности

- Все эндпоинты API (chats, messages, members/admins, subscriptions, updates, uploads, answers, me)
- Типизированные DTO для всех объектов спеки
- Ретраи с экспоненциальным бэкоффом (в т.ч. `attachment.not.ready`, `429`, `503`, сетевые ошибки)
- Локальный rate limiter 2 req/s на диалог/чат/канал
- Загрузка медиа в несколько шагов (запрос upload → загрузка файла → отправка сообщения)
- Обработка вебхуков с верификацией секрета `X-Max-Bot-Api-Secret`
- Long polling runner
- Верификация контакта из кнопки `request_contact`
- Типизированные исключения

## Ретраи

```php
use GeekCo\MaxPhpClient\Retry\RetryStrategy;

$client = ApiClient::create(
    // ...
    retryStrategy: new RetryStrategy(
        maxAttempts: 5,
        baseDelaySeconds: 1.0,
        maxDelaySeconds: 30.0,
        factor: 2.0,
    ),
);
```

По умолчанию ретраятся только идемпотентные методы (GET/PUT/DELETE), а также
`AttachmentNotReadyException` — всегда. Для ретраев неидемпотентных методов включите `retryOnNonIdempotent: true` или
задайте `customShouldRetry`.

## Rate limit

`RateLimiter` — локальный token bucket (2 req/s, бакет на 2) для каждого `chat_id`. Используется автоматически при
вызовах, связанных с чатом. При исчерпании бакета выбрасывается `RateLimitException`.

## Загрузка медиа

```php
use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Enum\UploadType;

$upload = $client->uploadMedia(UploadType::Image, '/path/to/photo.jpg');
$message = $client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(attachments: [new AttachmentRequest(type: AttachmentType::Image, token: $upload->token)]),
);
```

Клиент сам ждёт готовности вложения (`attachment.not.ready`) с экспоненциальными повторами перед отправкой сообщения.

## Вебхуки

```php
use GeekCo\MaxPhpClient\Webhook\WebhookHandler;
use Psr\Http\Message\ServerRequestInterface;

$handler = new WebhookHandler(secret: getenv('MAX_WEBHOOK_SECRET'));

if (!$handler->verify($request)) {
    // 401
}

/** @var list<GeekCo\MaxPhpClient\Dto\Update> $updates */
$updates = $handler->decode($request);
```

Создание подписки:

```php
$client->createSubscription(
    url: 'https://example.com/webhook',
    updateTypes: ['message_created', 'message_callback'],
    secret: 'my-secret',
);
```

Секрет подписки: 5–256 символов `[a-zA-Z0-9_-]`.

## Long polling

```php
use GeekCo\MaxPhpClient\LongPolling\LongPollingRunner;

$runner = new LongPollingRunner(
    api: $client,
    handler: static function (Update $update): bool {
        // обработать событие
        return true; // false — остановить цикл
    },
);

$lastMarker = $runner->run();
```

Long polling ограничен по скорости и хранению событий — подходит для разработки и тестирования, но не для production.

## Ошибки

| Исключение                    | Когда возникает                       |
|-------------------------------|---------------------------------------|
| `RateLimitException`          | HTTP 429 или локальный rate limiter   |
| `AttachmentNotReadyException` | код ошибки `attachment.not.ready`     |
| `ApiException`                | любой ответ 4xx/5xx с `ErrorResponse` |
| `NetworkException`            | транспортная ошибка PSR-18            |
| `InvalidResponseException`    | невалидный JSON / структура ответа    |
| `InvalidArgumentException`    | некорректные аргументы вызова         |

```php
use GeekCo\MaxPhpClient\Exception\MaxApiException;

try {
    $client->getChat($chatId);
} catch (MaxApiException $e) {
    if ($e instanceof ApiException) {
        printf('[%d] %s', $e->statusCode, $e->getError()?->message);
    }
}
```

## Безопасность

- Верификация контакта: `hash_equals(hash_hmac('sha256', $normalizedVcf, $accessToken), $hash)`,
  `\r\n` в `vcf_info` заменяются на реальные переносы строк.
- Секреты и токены никогда не логируются.
- Все URL валидируются (`https://`, без SSRF).
- Постоянновременное сравнение секретов через `hash_equals`.

## Разработка

```bash
docker compose run --rm app composer install
docker compose run --rm app vendor/bin/phpunit
docker compose run --rm app vendor/bin/phpstan analyse --no-progress
docker compose run --rm app composer run lint     # php-cs-fixer: проверка форматирования
docker compose run --rm app composer run format   # php-cs-fixer: авто-исправление
docker compose run --rm app composer run coverage # phpunit + порог покрытия (95% строк)
```

Покрытие кода тестами — 100% строк/методов (проверяется гейтом `composer run coverage`, порог 95%). Этот же набор проверок
прогоняется в CI на каждый push и PR (см. `.github/workflows/ci.yml`).

### Интеграционные тесты

Смоук-тесты против реального API (`tests/Integration/SmokeTest.php`, группа `integration`) выполняют
read-only вызовы (`getMe`, `getSubscriptions`, `getUpdates`, `getMessages`) и запускаются отдельно:

```bash
MAX_API_TOKEN=<token> docker run --rm --network host \
  -v "$(pwd)":/var/www/html -w /var/www/html \
  -e MAX_API_TOKEN="$MAX_API_TOKEN" \
  ghcr.io/geekcodev/php:8.4-bookworm vendor/bin/phpunit --group integration
```

- Требуется переменная окружения `MAX_API_TOKEN` (из `.env`).
- Цепочка сертификатов Минцифры лежит в `tests/Fixtures/max-ca-chain.pem` и используется для проверки TLS.
- В Docker-сети (`docker compose run`) TLS до `platform-api2.max.ru` может блокироваться — используйте `--network host`.
- Без доступа/токена тесты пропускаются, а не падают.

## Спецификация

OpenAPI-спецификация API: https://github.com/geekcodev/max-openapi
