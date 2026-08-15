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
    accessToken: getenv('MAX_API_TOKEN'),
);

$me = $client->getMe();

$message = $client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(text: 'Привет из PHP!'),
);
```

Токен передаётся в заголовке `Authorization` **без** префикса `Bearer`. Передача токена через query-параметры не
поддерживается.

## Переменные окружения

| Переменная           | Обязательная | Назначение                                                                        |
|----------------------|--------------|-----------------------------------------------------------------------------------|
| `MAX_API_TOKEN`      | да           | Access token бота; передаётся в заголовке `Authorization` без префикса `Bearer`   |
| `MAX_WEBHOOK_SECRET` | нет          | Секрет вебхука (заголовок `X-Max-Bot-Api-Secret`); 5–256 символов `[a-zA-Z0-9_-]` |

Пример `.env`:

```bash
MAX_API_TOKEN=your-bot-access-token
MAX_WEBHOOK_SECRET=your-webhook-secret
```

## Возможности

- Все эндпоинты API (chats, messages, members/admins, subscriptions, updates, uploads, answers, me)
- Типизированные DTO для всех объектов спеки
- Ретраи с экспоненциальным бэкоффом (в т.ч. `attachment.not.ready`, `429`, `503`, сетевые ошибки)
- Локальный rate limiter 2 req/s на диалог/чат/канал
- Загрузка медиа в несколько шагов (запрос upload → загрузка файла → отправка сообщения)
- Обработка вебхуков с верификацией секрета `X-Max-Bot-Api-Secret`
- Long polling runner
- Верификация контакта из кнопки `request_contact`
- Верификация стартовых данных мини-приложения (`WebAppDataValidator`)
- Типизированные исключения

## Примеры

Полностью рабочие примеры — в каталоге `examples/`. Требуются переменные из `.env` (`MAX_API_TOKEN`, для вебхука —
`MAX_WEBHOOK_SECRET`).

**Запуск через Docker** (рекомендуется; PHP/Composer на машине не нужны, зависимости ставятся автоматически, переменные
берутся из `.env`). Примеры запускаются через сервис `examples` из `docker-compose.yml` (`docker compose run`):

```bash
./examples/run.sh echo-bot-long-polling.php
./examples/run.sh echo-bot-webhook.php    # слушает http://localhost:8080
```

Для любого другого примера укажите его имя: `./examples/run.sh send-to-user.php`.

Вебхук-пример использует встроенный PHP-сервер на `localhost:8080`. API принимает вебхуки только по HTTPS на публичном
адресе, поэтому для локального тестирования нужен туннель до этого порта, например
`cloudflared tunnel --url http://localhost:8080` или `ngrok http 8080`, — полученный `https://...` адрес укажите в
`createSubscription()`. Порт можно переопределить: `MAX_EXAMPLES_PORT=9090 ./examples/run.sh echo-bot-webhook.php`.

**Запуск локально** (PHP >= 8.4 + Composer):

```bash
source .env
composer install
php examples/echo-bot-long-polling.php
php -S 0.0.0.0:8080 examples/echo-bot-webhook.php
```

Список примеров:

| Файл                                 | Что показывает                                                                     |
|--------------------------------------|------------------------------------------------------------------------------------|
| `examples/echo-bot-webhook.php`      | Вебхук-бот: верификация секрета, разбор апдейтов, эхо, ответ на колбэки (`php -S`) |
| `examples/echo-bot-long-polling.php` | Long polling-бот с тем же обработчиком апдейтов                                    |
| `examples/inline-keyboard.php`       | Отправка inline-клавиатуры (кнопки callback/link)                                  |
| `examples/send-media.php`            | Загрузка медиа и отправка с подписью, форматированием и `disable_link_preview`     |
| `examples/send-to-user.php`          | Идентификация пользователя и отправка ему личного сообщения                        |
| `examples/set-commands.php`          | Установка команд бота (`editBotCommands`)                                          |
| `examples/verify-contact.php`        | Верификация контакта из кнопки `request_contact`                                   |
| `examples/verify-webapp-data.php`    | Верификация стартовых данных мини-приложения (`WebAppDataValidator`)               |

**Как определить пользователя и отправить ему сообщение.** Бот получает `user_id` и `chat_id` диалога из любого апдейта
(`$update->user?->userId`, `$update->chatId`). Для событий сообщений и колбэков эти поля берутся из
`message.sender`/`message.recipient`, если их нет на верхнем уровне (см. раздел «Вебхуки»). Сообщение пользователю
отправляется через `Recipient(userId: ...)`; подробная информация о пользователе — через
`getChatMembers($chatId, [$userId])`
(аватар, описание, роль админа) или `getChat($chatId)->dialogWithUser`.

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

API MAX ограничивает все запросы **30 rps** на `platform-api2.max.ru` и **2 req/s** на диалог/чат/канал для
отправки/редактирования/удаления сообщений и ответов на callback.

Клиент применяет оба лимита локально:

- **Глобальный** (`HttpClient`): token bucket 30 req/s (бакет 30), ожидание при исчерпании — запросы просто
  задерживаются, исключений нет. Настраивается опцией `global_rate_limiter` в `ApiClient::create()`.
- **Per-chat** (`RateLimiter`): token bucket 2 req/s (бакет 2) для каждого `chat_id`. Используется автоматически при
  вызовах, связанных с чатом; при исчерпании выбрасывается `RateLimitException`. Для `editMessage`/`deleteMessage`/
  `sendAnswer` per-chat лимит не применяется (нет `chat_id`) — глобальный предохранитель всё равно действует.

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

## Ответ на callback

```php
use GeekCo\MaxPhpClient\Dto\NewMessageBody;

// Обновить сообщение с кнопками и/или показать одноразовое уведомление
$client->sendAnswer(
    callbackId: $update->callback?->callbackId ?? '',
    message: new NewMessageBody(text: 'Кнопка нажата'),
    notification: 'Обрабатываю…',
);
```

API требует `message` **или** `notification` — при вызове без обоих клиент выбрасывает
`InvalidArgumentException` до запроса.

## Вебхуки

```php
use GeekCo\MaxPhpClient\Webhook\WebhookHandler;
use Psr\Http\Message\ServerRequestInterface;

$handler = new WebhookHandler(secret: getenv('MAX_WEBHOOK_SECRET'));

if (!$handler->verify($request)) {
    // 401
}

/** @var GeekCo\MaxPhpClient\Dto\Update|list<GeekCo\MaxPhpClient\Dto\Update> $updates */
$updates = $handler->decode($request);
if ($updates instanceof GeekCo\MaxPhpClient\Dto\Update) {
    $updates = [$updates];
}
```

Для событий `message_created`/`message_edited`/`message_callback` поля `user` и `chat_id` могут отсутствовать на верхнем
уровне — они автоматически берутся из `message.sender`/`message.recipient` (и `callback.message`). Объект
`Update` дополнительно содержит поля `user_locale`, `title`, `payload`, `muted_until`, `message_id`, `user_id`,
`inviter_id`, `admin_id` — они заполняются для соответствующих типов событий, для остальных равны `null`.
`user` (объект `User`) может быть `null` (например, для `message_removed`).

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
- Верификация стартовых данных мини-приложения (`WebAppDataValidator`):
  `secret_key = HMAC-SHA256('WebAppData', token)`, подпись `launch_params` по алгоритму
  https://dev.max.ru/docs/webapps/validation.
- `WebAppDataValidator::resolve()` дополнительно извлекает `user_id`/`chat_id` (DTO `WebAppIdentity`) и при заданном
  `maxAge` отбрасывает устаревшие `auth_date` (replay-защита). `verifyFromUrl()`/`resolveFromUrl()` понимают как
  query-параметр `?WebAppData=...`, так и фрагмент `#WebAppData=...`.
- Секреты и токены никогда не логируются.
- Все URL валидируются (`https://`, без SSRF).
- Постоянновременное сравнение секретов через `hash_equals`.

## Интеграция в фреймворки

Ядро framework-agnostic (PSR-7/17/18). Адаптация сводится к регистрации `ApiClient` как синглтона в DI-контейнере и
пробросу `WebhookHandler` в контроллер:

```php
// Регистрация в DI-контейнере (Laravel ServiceProvider / Symfony service).
// Клиент создаётся один раз и внедряется в сервисы и контроллеры.
$psrFactory = new HttpFactory();

$container->singleton(ApiClient::class, static fn (): ApiClient => ApiClient::create(
    httpClient: $container->get(ClientInterface::class), // PSR-18 клиент фреймворка
    requestFactory: $psrFactory,
    streamFactory: $psrFactory,
    uriFactory: $psrFactory,
    accessToken: $config['api_token'],
));

// Контроллер вебхука. Секрет проверяется через verify() (иначе 401),
// невалидный payload — 400. Ответ 200 обязателен в течение 30 сек,
// иначе API повторит доставку по экспоненте.
public function webhook(ServerRequestInterface $request): ResponseInterface
{
    if (!$this->handler->verify($request)) {
        return new Response(401);
    }

    try {
        $updates = $this->handler->decode($request);
    } catch (InvalidResponseException) {
        return new Response(400);
    }

    if ($updates instanceof Update) {
        $updates = [$updates];
    }

    foreach ($updates as $update) {
        $this->dispatch($update);
    }

    return new Response(200);
}
```

Практики для production:

- Храните `user_id` (`$update->user->userId`) и `chat_id` (`$update->chatId`) в своей БД; личные сообщения отправляйте
  через `Recipient(userId: ...)`.
- Загрузка медиа выполняется клиентом целиком (`uploadMedia`), включая ожидание готовности вложения — не дублируйте этот
  код в проекте.
- Для long polling есть готовый `LongPollingRunner`; для production используйте вебхуки.
- Обработку апдейтов держите асинхронной (очередь), чтобы укладываться в лимит ответа webhook 30 сек.

## Разработка

```bash
docker compose run --rm app composer install
docker compose run --rm app vendor/bin/phpunit
docker compose run --rm app vendor/bin/phpstan analyse --no-progress
docker compose run --rm app composer run lint     # php-cs-fixer: проверка форматирования
docker compose run --rm app composer run format   # php-cs-fixer: авто-исправление
docker compose run --rm app composer run coverage # phpunit + порог покрытия (95% строк)
```

Покрытие кода тестами — 100% строк/методов (проверяется гейтом `composer run coverage`, порог 95%). Этот же набор
проверок прогоняется в CI на каждый push и PR (см. `.github/workflows/ci.yml`).

### Интеграционные тесты

Смоук-тесты против реального API (`tests/Integration/SmokeTest.php`, группа `integration`) выполняют read-only вызовы
(`getMe`, `getSubscriptions`, `getUpdates`, `getMessages`) и запускаются отдельно:

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

## История изменений

### v1.0.7 — sendAnswer: notification, fail-fast (см. `RELEASE_NOTES_v1.0.7.md`)

- `ApiClient::sendAnswer()`: добавлен опциональный `?string $notification` (одноразовое уведомление).
- API требует `message` или `notification`: вызов без обоих → `InvalidArgumentException` (раньше клиент слал `{}`
  и получал 400 `message or notification required`); пустой `NewMessageBody` тоже отклоняется.

### v1.0.6 — sendAnswer

`{}`, фикс парсинга администраторов, глобальный rate limit 30 rps, синхронизация с max-openapi (см.
`RELEASE_NOTES_v1.0.6.md`)

- `ApiClient::sendAnswer()`: при `message === null` тело `{}` (раньше — 400 Empty request body).
- `ChatAdminPermission`: добавлены deprecated-значения `post_edit_delete_message`, `edit_message`, `delete_message`
  — `getBotMembership()`/`getChatAdmins()`/`getChatMembers()` больше не падают на чатах со старыми правами.
- `ApiClient::addChatAdmin()`: опциональный `?int $marker` (тело запроса); выдача deprecated-прав →
  `InvalidArgumentException`.
- `ApiClient::getChatAdmins()`: не шлёт query `marker`/`count` (параметры сохранены, помечены deprecated).
- Глобальный rate limit 30 rps: `HttpClient` ожидает при исчерпании бакета; опция `global_rate_limiter`
  в `ApiClient::create()` (дефолт 30 req/s).
- Документация: `join_time` — мс; глобальный лимит 30 rps на `platform-api2.max.ru`.

### v1.0.3 — синхронизация с max-openapi, фикс парсинга Update

**Ломающие изменения** (см. `RELEASE_NOTES_v1.0.3.md`):

- `ApiClient::getPinnedMessage()`: ответ читается из поля `message` (было `pin`).
- `ApiClient::sendBotAction()`: тело `{"action": ...}` (было `{"type": ...}`).
- `ApiClient::addChatAdmin()`: тело `{"admins": [{...}]}` (было `{...}` напрямую).
- `ApiClient::getChatAdmins()`: `ChatAdminsResult::$admins` (тип `ChatAdmin`) → `$members` (тип `ChatMember`).
- `ApiClient::editBotCommands()`: возвращает `BotCommandsResult` вместо `SuccessResponse`.
- `VideoInfo`: поля `token, urls, thumbnail, width, height, duration` (было `video_token, file_name, size, url`).
- `NewMessageLink`: поля `type, mid, chat` (было `type, url, token`).
- `Chat::$icon` и `EditChatBody::$icon`: теперь `Image`/`ChatIcon` (объект `{url}` / `{url, payload}`).

**Исправления:**

- `Update::fromArray()`: `user`/`chat_id` фолбэки из `message.sender`/`message.recipient` и `callback.message`;
  `Update::$user` теперь nullable; добавлены поля `user_locale`, `title`, `payload`, `muted_until`, `message_id`,
  `user_id`, `inviter_id`, `admin_id`.
- `Recipient::$chatType` — новое поле `chat_type`.
