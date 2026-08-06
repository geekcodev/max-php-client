# AGENTS.md

> Проектный контекст и рабочие правила для разработчиков и ИИ-агентов (включая opencode).
> Читай этот файл **целиком** в начале работы — он заменяет перечитывание спецификации и задаёт
> обязательный процесс проверок. Пользовательскую документацию (быстрый старт, примеры, интеграция
> в фреймворки) смотри в `README.md`.

## 1. О проекте

- **Что это.** Публичная PHP-библиотека **`geekcodev/max-php-client`** — framework-agnostic клиент для **MAX Messenger
  Bot API** (мессенджер MAX, https://max.ru). Цель — production-grade ядро, которое переиспользуется в интеграционных
  модулях: фреймворк-мосты (Laravel, Symfony) делаются **отдельными пакетами** поверх этого клиента и от ядра не
  зависят.
- **Статус.** Выпущена **v1.0.0**: git-тег `v1.0.0`, GitHub Release, публикация на **Packagist**
  (`geekcodev/max-php-client`). `version` в `composer.json` **не указывается** — Packagist берёт версию из тегов.
- **Лицензия.** MIT (c) 2026 Evgeny Semenov.
- **Язык.** Рабочий язык общения с пользователем — **русский**.

## 2. Ветки и состояние git

- `main` — стабильная, соответствует выпущенным релизам (сейчас v1.0.0).
- `dev` — рабочая ветка; изменения сначала здесь.
- Релизный процесс: PR `dev → main` → тег `vX.Y.Z` → GitHub Release → автопубликация на Packagist.
- База истории: `7929124 chore: create max api php client` → `8ef7397 fix: README.md and CI workflow` →
  `6ba08ce fix(ci): build image with xdebug and set composer root version` (вершина `dev`).
- `.env` — untracked (в `.gitignore`): хранит `MAX_API_TOKEN`, `MAX_WEBHOOK_SECRET`. **Никогда не коммитить**
  и не логировать значения. Коммиты и push делает пользователь (в окружении нет credential.helper/gh) — без явного
  запроса не коммить.

## 3. Правила для ИИ-агентов

1. В начале работы прочитай `AGENTS.md` и `README.md`.
2. **Не коммить и не пушить без явного запроса пользователя.**
3. Перед завершением любой задачи, менявшей код, прогони обязательный Gate (раздел 7) целиком. Результаты не подменяй;
   недоступный шаг честно указывай в отчёте, а не пропускай молча.
4. Не выдумывай сигнатуры и эндпоинты: сверяйся с разделом 9 или спецификацией `max-openapi`.
5. Если для задачи чего-то не хватает (токен, сеть, контейнер) — скажи об этом, а не упрощай задачу молча.
6. Ответы — краткие и по делу; в коде — без лишних комментариев.

## 4. Структура репозитория

```
src/                          клиент, DTO, enums, исключения, сервисные компоненты
tests/                        PHPUnit: unit-тесты + Integration/SmokeTest (группа integration)
examples/                     рабочие примеры ботов + run.sh (docker-запуск без локального PHP)
scripts/check-coverage.php    порог покрытия строк (по умолчанию 95%)
.github/workflows/ci.yml      CI: quality + integration
Dockerfile                    PHP 8.4, опциональный Xdebug (ARG INSTALL_XDEBUG=false)
docker-compose.yml            сервис app, user 1000:1000, volume ./, .env пробрасывается
composer.json                 PSR-4, PHP ^8.4
phpunit.xml                   failOnRisky/failOnWarning; группа integration исключена по умолчанию
phpstan.neon                  level max
.php-cs-fixer.dist.php        PSR-12
.env.example                  MAX_API_TOKEN, MAX_WEBHOOK_SECRET (эталон имён переменных)
```

`composer.lock`, `.phpunit.cache/`, `build/`, `vendor/` — в `.gitignore` (для библиотеки lock не коммитится).

## 5. Архитектура и ключевые контракты

### Слои

| Слой        | Ключевые классы                                   | Назначение                                                                  |
|-------------|---------------------------------------------------|-----------------------------------------------------------------------------|
| API         | `ApiClient`, `ApiClient::create()`                | Единственная точка входа; фабрика компонентов                               |
| Transport   | `HttpClient`, `RequestBuilder`, `ResponseDecoder` | PSR-18 запросы, сборка URI/заголовков, разбор ответов, нормализация ошибок  |
| Retry       | `RetryStrategy`                                   | Экспоненциальный бэкофф: 429/5xx/сетевые сбои/`attachment.not.ready`        |
| RateLimit   | `RateLimiter`                                     | Token bucket, 2 запроса/сек                                                 |
| Webhook     | `WebhookHandler`                                  | Парсинг Update, верификация секрета (`hash_equals`)                         |
| LongPolling | `LongPollingRunner`                               | Обёртка над `getUpdates` (только dev/тесты)                                 |
| Upload      | `Uploader`                                        | Multipart-загрузка медиа (требует `ext-fileinfo`)                           |
| Security    | `ContactVerifier`                                 | Верификация контакта по кнопке `request_contact`                            |
| Internal    | `Internal\Json`                                   | Единственное место работы с JSON (кодирование/декодирование с исключениями) |
| Dto         | `src/Dto/*` (36 классов)                          | Типизированные модели запросов и ответов                                    |
| Enum        | `src/Enum/*` (8)                                  | Строго типизированные значения                                              |
| Exception   | `src/Exception/*` (7)                             | Иерархия типизированных ошибок                                              |

### Контракты компонентов

- **`ApiClient::create(array $options): self`** — опции: `access_token` (обязательный), `http_client` (PSR-18),
  `request_factory` / `stream_factory` (PSR-17), `base_uri`, `retry_strategy`, `rate_limiter`. Внутри собирает
  `HttpClient` (transport + retry + rate limit) и `Uploader`. Используется во всех примерах (`examples/bootstrap.php`).
- **`WebhookHandler::decode(): Update|list<Update>`** — критичный нюанс: ответ может быть **одним объектом**
  **или** списком. Итерация без проверки ломает foreach:
  ```php
  $updates = $handler->decode($body);
  $updates = $updates instanceof Update ? [$updates] : $updates;
  foreach ($updates as $update) { ... }
  ```
  Ответы эндпоинта: неверный секрет → HTTP 401, невалидный body → HTTP 400, успех → HTTP 200 (обязателен в течение 30
  сек, иначе API повторит доставку).
- **`RetryStrategy`** — по умолчанию ретраит: 429, 5xx, сетевые сбои, `attachment.not.ready`; только идемпотентные
  методы; число попыток и задержки настраиваются.
- **`RateLimiter`** — дефолт 2 запроса/сек (лимит API на диалог/чат/канал).
- **`LongPollingRunner`** — **не для production**: спека ограничивает скорость и хранение событий.
- **`Uploader::upload(UploadType $type, string $filePath)`** — multipart собирается в seekable `php://temp`-поток (файл
  копируется по чанкам 8 КБ, без загрузки в память целиком; поток безопасен для повторов при ретраях). После загрузки
  **ждать** перед отправкой сообщения — иначе `attachment.not.ready` (ретраится автоматически).
- **`ContactVerifier`** — верификация: `hash_equals(hash_hmac('sha256', $normalizedVcf, $accessToken), $hash)`; в
  `vcf_info` `\r\n` нормализуется в реальные переносы строк.

### Соглашения DTO

- Все DTO — `final readonly`, типизированные nullable-поля, валидация типов в `fromArray()`.
- `toArray()` — для запросов. Конструкторы `create()` есть только у `NewMessageBody`, `EditChatBody`,
  `AttachmentRequest`, `PinMessageBody`.
- Вложения (`Attachment`) — discriminated по `token`: 7 типов payload (+ image/photo).
- JSON-кодирование/декодирование — только через `Internal\Json`, никогда напрямую `json_encode`/`json_decode`.
- ID: `message_id` / `callback_id` / `messageId` — **строки**; `chat_id` / `user_id` — **int64**.

### Иерархия исключений

`MaxApiException` (базовое) → `ApiException`, `AttachmentNotReadyException`, `InvalidArgumentException`,
`InvalidResponseException`, `NetworkException`, `RateLimitException`. Для бизнес-обработки ловить
`MaxApiException`. В `README.md` — раздел «Ошибки» с таблицей кодов и исключений.

## 6. Соглашения по коду

- PHP **8.4**, `declare(strict_types=1)` во всех файлах, PSR-12 (php-cs-fixer), PHPStan **level max**.
- Namespace `GeekCo\MaxPhpClient` (тесты `GeekCo\MaxPhpClient\Tests`), PSR-4.
- SOLID / DRY / KISS: единая ответственность, открытость к расширению, без избыточной абстракции и дублирования.
  PSR-стандарты (PSR-4/7/17/18) — там, где уместно.
- Не добавлять комментарии без необходимости. `@codeCoverageIgnore` — только для defensive-веток, недостижимых в тестах
  (например, `file_get_contents()` вернул `false`).
- Тесты обязательны для нового кода: unit на компоненты; HTTP-слой — через `tests/Support/MockHttpClient`
  (PSR-18). Интеграционные — read-only, группа `integration`, без токена `markTestSkipped` (не падать).
- Новые файлы в `examples/` — со смысловым именем, с `bootstrap.php`, без токенов в коде.

### OWASP Top 10 (обязательно при написании кода)

- Не доверять входящим данным: webhook body, query/path-параметры, поля из JSON (A03 — injection).
- Постоянновременное сравнение секретов — только `hash_equals` (A07 — identification failures).
- Не логировать: access token, secret, `vcf_info`, callback payload (A02, A09).
- SSRF: url из вебхуков/подписок — валидация `https://` + домен; upload-URL строго `https://`.
- Корректное кодирование в PSR-7; никаких конкатенаций URL (A03).
- Ограничение входных данных по размерам и типам.

## 7. Локальная разработка и обязательный Gate

PHP и Composer на хосте **не установлены** — весь запуск через Docker:

```bash
docker compose run --rm app bash                       # интерактивная оболочка PHP 8.4
docker compose run --rm app composer install
docker compose run --rm app composer run lint          # php-cs-fixer --dry-run (PSR-12)
docker compose run --rm app composer run format        # php-cs-fixer: авто-исправление
docker compose run --rm app vendor/bin/phpstan analyse # level max
docker compose run --rm app vendor/bin/phpunit         # unit-тесты
docker compose run --rm app composer run coverage      # тесты + проверка покрытия ≥95%
```

Запуск примеров без локального PHP:

```bash
examples/run.sh echo-bot-long-polling.php
examples/run.sh echo-bot-webhook.php   # слушает http://localhost:8080
```

Интеграционные смоук-тесты (read-only, реальный API, нужен `MAX_API_TOKEN`):

```bash
source .env && docker run --rm --network host \
  -v "$(pwd)":/var/www/html -w /var/www/html \
  -e MAX_API_TOKEN="$MAX_API_TOKEN" \
  ghcr.io/geekcodev/php:8.4-bookworm vendor/bin/phpunit --group integration
```

Нюансы интеграционных тестов:

- TLS до `platform-api2.max.ru` из Docker-сети (`docker compose run`) блокируется — только `--network host`.
- Цепочка сертификатов Минцифры — `tests/Fixtures/max-ca-chain.pem`.
- Без токена/доступа тесты пропускаются (`markTestSkipped`), а не падают.

### Обязательная последовательность (Gate) перед завершением задачи

После изменений в PHP-коде (`src/`, `tests/`, `examples/`):

1. **Lint**: `composer run lint` → 0 файлов с правками.
2. Если есть правки — `composer run format`, затем повторить lint.
3. **Статика**: `vendor/bin/phpstan analyse` → 0 ошибок.
4. **Тесты**: `vendor/bin/phpunit` → все зелёные (failOnRisky/failOnWarning).
5. **Покрытие**: `composer run coverage` → ≥95% строк.

Все шаги обязательны. Если шаг недоступен в окружении — сообщить пользователю и указать в отчёте.

## 8. CI/CD и релизы

- **Job `quality`**: сборка образа с Xdebug (`--build-arg INSTALL_XDEBUG=true`), lint, phpstan, phpunit + coverage gate.
  Job-level `env: IMAGE: max-php-client:ci`.
- **Job `integration`**: смоук-тесты реального API; без `MAX_API_TOKEN` — шаги пропускаются, не падают (секрет
  передаётся только через job-level `env`, `secrets` в `if` на уровне job запрещены GitHub Actions).
- Ключевые детали workflow: `-e COMPOSER_ROOT_VERSION=dev-main` во всех шагах (обход отсутствия git-метаданных в
  volume), `-e XDEBUG_MODE=coverage` для генерации отчёта, кэш `vendor` по `composer.json`.
- **Релиз**: merge PR `dev → main` → `git tag vX.Y.Z && git push origin vX.Y.Z` → GitHub Release из тега → Packagist
  (автообновление по webhook). Тег ставится только на `main`.

## 9. Источник истины (API MAX)

Спецификация: **https://github.com/geekcodev/max-openapi** (OpenAPI 3.1.0). Сервер:
**https://platform-api2.max.ru**.

### Аутентификация

- Заголовок `Authorization: <access_token>` — **без** `Bearer ` префикса, токен голым.
- Передача токена через query-параметры **не поддерживается**.

### Критичные оговорки

- Домен **`platform-api2.max.ru`** (НЕ `platform-api.max.ru`).
- Нужен сертификат Минцифры в доверенных (для локальных сред — кастомный CA).
- HTTP-вебхуки не поддерживаются — только HTTPS.
- Long Polling ограничен по скорости и хранению событий — **не для production**.
- `GET /chats` **deprecated с июня 2026** — подписка на `bot_added`/`bot_started` + хранение chat_id у себя.
- `type=photo` deprecated → `type=image`.

### Rate limits (на диалог/чат/канал)

- Отправка/редактирование/удаление сообщений и ответы на callback: **макс. 2/сек**.

### Загрузка медиа (`POST /uploads`)

- `type` — **query-параметром**; ответ: `{url, token}`.
- Домены: `file` → `https://fu.oneme.ru`, `image` → `https://iu.oneme.ru`, `video`/`audio` →
  `https://vu.okcdn.ru`.
- Лимиты: image 50 МБ или 7680×7680 px; video 250 МБ; audio 256 МБ или 60 мин; file 4 ГБ.
- После загрузки **ждать перед отправкой** сообщения, иначе `attachment.not.ready` — ретрай с экспоненциальной
  задержкой.

### Вебхуки (`POST /subscriptions`)

- Модель: событие → POST на webhook с объектом `Update`; проверка TLS; заголовок `X-Max-Bot-Api-Secret`
  (если задан `secret`); эндпоинт обязан ответить HTTP 200 за 30 сек; повторы 60с→150с→375с→… (10 попыток за ~8 часов);
  при неуспехе 8 ч — автоотписка.
- Требования: HTTPS :443, доверенный CA (или Минцифры), без самоподписных, домен = CN/SAN, полная цепочка.
- `secret`: 5–256 символов, `[a-zA-Z0-9_-]`.
- Активная webhook-подписка отключает Long Polling.

### Ключевые схема-соглашения

- Почти все timestamp — **Unix в миллисекундах** (`last_activity_time`, `timestamp`, `last_event_time`), исключение:
  `join_time` — **секунды**.
- Пагинация — `marker` (int64, nullable) + `count`.
- `message_id` / `callback_id` / `messageId` (path) — строки; `chat_id` / `user_id` — int64.
- Ошибки: `ErrorResponse {code, message, error?}`. HTTP-коды: 400, 401, 404, 405, 429, 503.
- Успех операций: `SuccessResponse {success, message?}`.
- Контакт по кнопке `request_contact`: `hash = HMAC-SHA256(access_token, vcf_info)`; в `vcf_info` `\r\n`
  заменять на реальные переносы строк.

### Эндпоинты

| Метод  | Путь                                      | operationId          | Описание                                                                        |
|--------|-------------------------------------------|----------------------|---------------------------------------------------------------------------------|
| GET    | `/me`                                     | `getMe`              | Инфо о боте (BotInfo)                                                           |
| PATCH  | `/me/commands`                            | `editBotCommands`    | Команды бота (макс 32; `[]` — удалить все)                                      |
| GET    | `/chats`                                  | `getChats`           | **DEPRECATED**                                                                  |
| GET    | `/chats/{chatId}`                         | `getChat`            | Инфо о чате/канале                                                              |
| PATCH  | `/chats/{chatId}`                         | `editChat`           | title/icon/pin/notify                                                           |
| POST   | `/chats/{chatId}/actions`                 | `sendBotAction`      | SenderAction                                                                    |
| GET    | `/chats/{chatId}/pin`                     | `getPinnedMessage`   | message или null                                                                |
| PUT    | `/chats/{chatId}/pin`                     | `pinMessage`         | body: message_id, notify?                                                       |
| DELETE | `/chats/{chatId}/pin`                     | `unpinMessage`       | Открепление                                                                     |
| GET    | `/chats/{chatId}/members/me`              | `getBotMembership`   | Членство бота (ChatMember)                                                      |
| DELETE | `/chats/{chatId}/members/me`              | `removeBotFromChat`  | Удаление бота                                                                   |
| GET    | `/chats/{chatId}/members/admins`          | `getChatAdmins`      | Список админов + marker                                                         |
| POST   | `/chats/{chatId}/members/admins`          | `addChatAdmin`       | Назначить админа (PUT-семантика)                                                |
| DELETE | `/chats/{chatId}/members/admins/{userId}` | `removeChatAdmin`    | Снять админа                                                                    |
| GET    | `/chats/{chatId}/members`                 | `getChatMembers`     | Участники; query: user_ids?, marker?, count?(1-100, default 20)                 |
| POST   | `/chats/{chatId}/members`                 | `addChatMembers`     | body: user_ids (макс 100); ответ + failed_user_ids/details                      |
| DELETE | `/chats/{chatId}/members`                 | `removeChatMember`   | query: user_id (обяз.), block?(bool, default false)                             |
| GET    | `/subscriptions`                          | `getSubscriptions`   | Список webhook-подписок                                                         |
| POST   | `/subscriptions`                          | `createSubscription` | body: url(https), update_types?, secret?                                        |
| DELETE | `/subscriptions`                          | `deleteSubscription` | query: url (обяз.)                                                              |
| GET    | `/updates`                                | `getUpdates`         | Long Polling; query: limit(1-1000, d100), timeout(0-90, d30), marker?, types?   |
| POST   | `/uploads`                                | `uploadMedia`        | query: type (обяз.); ответ {url, token?}                                        |
| GET    | `/messages`                               | `getMessages`        | query: chat_id? message_ids?(csv), from?, to?, count?(1-100, d50)               |
| POST   | `/messages`                               | `sendMessage`        | query: user_id? chat_id? (одно из), disable_link_preview?; body: NewMessageBody |
| PUT    | `/messages`                               | `editMessage`        | query: message_id (обяз.); body: NewMessageBody                                 |
| DELETE | `/messages`                               | `deleteMessage`      | query: message_id (обяз.)                                                       |
| GET    | `/messages/{messageId}`                   | `getMessageById`     | path: messageId (строка, `[a-zA-Z0-9_-]+`)                                      |
| GET    | `/videos/{videoToken}`                    | `getVideoInfo`       | Инфо о видео (VideoInfo)                                                        |
| POST   | `/answers`                                | `sendAnswer`         | query: callback_id (обяз.); body: {message?: NewMessageBody}                    |

### Enums

- **ChatType**: `chat`, `channel`, `dialog`
- **ChatStatus**: `active`, `removed`, `left`, `closed`
- **SenderAction**: `typing_on`, `sending_photo`, `sending_video`, `sending_audio`, `sending_file`
- **UploadType**: `image`, `video`, `audio`, `file`
- **TextFormat**: `markdown`, `html`
- **ChatAdminPermission**: `read_all_messages`, `add_remove_members`, `add_admins`, `change_chat_info`,
  `pin_message`, `write`, `can_call`, `edit_link`, `edit`, `delete`, `view_stats`
- **UpdateType**: `bot_added`, `bot_started`, `bot_stopped`, `bot_removed`, `chat_title_changed`,
  `dialog_cleared`, `dialog_muted`, `dialog_unmuted`, `dialog_removed`, `message_callback`,
  `message_created`, `message_edited`, `message_removed`, `user_added`, `user_removed`
- **AttachmentType**: `image`, `video`, `audio`, `file`, `sticker`, `inline_keyboard`, `location`, `share`
- **ButtonType**: `callback`, `link`, `request_contact`, `request_geo_location`, `open_app`, `message`,
  `clipboard`

### Ключевые объекты (DTO)

- `User` (user_id int64, first_name, last_name?, username?, is_bot, last_activity_time, name[deprecated])
- `UserWithPhoto` (+ description?, avatar_url?, full_avatar_url?)
- `BotInfo` = UserWithPhoto + commands?
- `ChatMember` = UserWithPhoto + last_access_time, is_owner, is_admin, join_time, permissions?, alias?
- `Chat` (chat_id, type, status, title?, icon?, last_event_time, participants_count, owner_id?, participants?,
  is_public, link?, description?, dialog_with_user?, messages_count?, pinned_message?)
- `Message` (sender?, recipient, timestamp, link?, body?, stat?, url?)
- `MessageBody` (mid, seq, text?, attachments?, caption?, format)
- `NewMessageBody` (text?, attachments?, link?, notify?, format) — attachments: `null`=без изменений,
  `[]`=удалить все
- `Attachment` (type, payload?) — payload discriminated по `token`, oneOf из 7 типов payload
- `AttachmentRequest` (type, payload{token?, url?, rows?})
- `InlineKeyboardButton` (type, text, payload?, url?, intent?, app_data?) — макс 210 кнопок / 30 рядов / 7 в ряду (3 для
  link/open_app/request_geo_location/request_contact)
- `Update` (update_type, timestamp, chat_id, user, is_channel?, message?, callback{callback_id, payload?, message}?)
- `Subscription` (url, update_types?)
- `ErrorResponse` (code, message, error?)

## 10. Частые ошибки (gotchas)

1. `WebhookHandler::decode()` возвращает `Update|list<Update>` — **не** итерировать без `instanceof`-проверки.
2. Токен — без `Bearer`; только заголовок, не query.
3. `attachment.not.ready` — загруженное вложение ещё не готово: ждать и ретраить.
4. `join_time` — секунды; остальные timestamp — миллисекунды.
5. Из Docker-сети TLS до API блокируется — только `--network host`.
6. Имя переменной — только `MAX_API_TOKEN` (старое `MAX_ACCESS_TOKEN` не используется).
7. `getChats` deprecated — chat_id хранить через подписку на `bot_added`/`bot_started`.
8. `mime_content_type()` требует `ext-fileinfo` (объявлено в `require` composer.json).
9. Секреты/токены/vcf_info/callback payload — никогда в логи и коммиты.
10. Версионирование — только git-тегами; `version` в composer.json не указывать.

## 11. Чек-лист «production-grade» (самооценка при доработках)

- [ ] CI зелёный: lint 0, phpstan 0, phpunit зелёные, покрытие ≥95%.
- [ ] Новый код покрыт unit-тестами (HTTP-слой — через MockHttpClient).
- [ ] Секретов нет в коде, логах, коммитах.
- [ ] Входные данные валидируются (DTO / WebhookHandler / параметры запросов).
- [ ] Документация (README, examples/, AGENTS.md) синхронна с реальным поведением кода и API.
- [ ] Релиз оформлен: merge в main → тег → GitHub Release → Packagist.
