# AGENTS.md

> Контекст проекта для AI-агентов. Прочитай этот файл в начале работы — он заменяет полное перечитывание спецификации.

## Суть проекта

PHP-клиент для **MAX Messenger Bot API** (мессенджер MAX, https://max.ru). Задача: универсальный, профессиональный,
production-grade клиент, который в дальнейшем послужит ядром для модулей интеграции в разные фреймворки (Laravel,
Symfony и т.д.). Клиент и его API должны быть framework-agnostic (PSR-совместимые интерфейсы), а фреймворк-мосты —
отдельными пакетами.

Рабочий язык общения с пользователем — **русский**.

## Состояние репозитория

- Коммит `c530ce9` (Initial commit), содержит `LICENSE`. Staged, но не закоммичено: `Dockerfile`, `docker-compose.yml`,
  `.gitignore`.
- `.env` (untracked, в `.gitignore`) — для хранения access token и прокидывания в docker-compose.
- LICENSE — MIT, Copyright (c) 2026 Evgeny Semenov.
- git user: `Evgeny Semenov <geekco@yandex.ru>`.

## Локальная разработка (Docker, PHP 8.4)

PHP и Composer в окружении хоста НЕ установлены (`php -v` пусто). Весь запуск — через Docker:

- `Dockerfile`: базовый образ `ghcr.io/geekcodev/php:8.4-bookworm`, опциональный Xdebug (`ARG INSTALL_XDEBUG=false` →
  `docker-php-ext-enable xdebug`, версия 3.4.7).
- `docker-compose.yml`: сервис `app`, контекст `.`, `user: 1000:1000`, volume `./:/var/www/html`,
  `extra_hosts: host.docker.internal:host-gateway`, `TZ=Europe/Moscow`, `XDEBUG_MODE=coverage`.
- `.env` пробрасывается в контейнер через `environment` (хранит токен API и т.п.).

Типовые команды:

```bash
docker compose run --rm app bash          # интерактивная оболочка с PHP 8.4
docker compose run --rm app composer install
docker compose run --rm app vendor/bin/phpunit
docker compose run --rm app vendor/bin/phpstan analyse
docker compose run --rm app composer run format   # php-cs-fixer: авто-исправление форматирования
docker compose run --rm app composer run lint     # php-cs-fixer: проверка форматирования (--dry-run)
```

Интеграционные смоук-тесты (группа `integration`, read-only вызовы реального API) запускаются отдельно и требуют
`MAX_API_TOKEN` из `.env`:

```bash
source .env && docker run --rm --network host \
  -v "$(pwd)":/var/www/html -w /var/www/html \
  -e MAX_API_TOKEN="$MAX_API_TOKEN" \
  ghcr.io/geekcodev/php:8.4-bookworm vendor/bin/phpunit --group integration
```

Нюансы интеграционных тестов:
- TLS до `platform-api2.max.ru` из Docker-сети (`docker compose run`) блокируется — использовать `--network host`.
- Цепочка сертификатов Минцифры — `tests/Fixtures/max-ca-chain.pem`.
- Без токена/доступа тесты пропускаются (`markTestSkipped`), а не падают.

Проверять код можно запуском внутри контейнера. Если контейнер не поднят / нужен доступ к внешним сервисам — код
проверяется статически или через пользователя.

## Обязательная проверка после любых изменений кода

После внесения изменений в PHP-код (`src/`, `tests/`) **перед завершением задачи** необходимо прогнать:

1. **Форматирование**: `docker compose run --rm app composer run lint` (php-cs-fixer, PSR-12). Если есть правки —
   запустить `composer run format`, затем повторить `lint` (должно быть 0 файлов).
2. **Статический анализ**: `docker compose run --rm app vendor/bin/phpstan analyse` (level max, 0 ошибок).
3. **Тесты**: `docker compose run --rm app vendor/bin/phpunit` (все зелёные).

Все три шага обязательны, результаты нельзя подменять. Если какой-то шаг недоступен в окружении — сообщить
пользователю и указать это в отчёте, а не молча пропускать.

## Источник истины

GitHub-репозиторий со спецификацией: **https://github.com/geekcodev/max-openapi** (OpenAPI 3.1.0). Сервер:
`https://platform-api2.max.ru`.

## Важные факты об API

### Аутентификация

- Заголовок `Authorization: <access_token>`. **Без** `Bearer ` префикса — токен передаётся голым.
- Передача токена через query-параметры НЕ поддерживается.

### Критичные оговорки из спеки

- Использовать домен `platform-api2.max.ru` (НЕ `platform-api.max.ru`).
- Нужен сертификат Минцифры в доверенных (для локальных сред может понадобиться кастомный CA).
- HTTP-вебхуки не поддерживаются — только HTTPS.
- Long Polling ограничен по скорости и хранению событий — не для production, только для разработки/тестирования.
- `GET /chats` **deprecated с июня 2026** — вместо него подписка на `bot_added`/`bot_started` и хранение chat_id у себя.
- `type=photo` deprecated → `type=image`.

### Rate limits (на диалог/чат/канал)

- Отправка/редактирование/удаление сообщений и ответы на callback: **макс. 2/сек**.

### Загрузка медиа (`POST /uploads`)

- `type` передаётся **query-параметром**; ответ: `{url, token}`.
- Домены загрузки: `file` → `https://fu.oneme.ru`, `image` → `https://iu.oneme.ru`, `video`/`audio` →
  `https://vu.okcdn.ru`.
- Лимиты: image 50МБ или 7680×7680px; video 250МБ; audio 256МБ или 60мин; file 4ГБ.
- После загрузки **ждать перед отправкой сообщения**, иначе ошибка `attachment.not.ready` — ретраить с экспоненциальной
  задержкой.

### Вебхуки (`POST /subscriptions`)

- Модель: событие → POST на webhook с объектом `Update`; проверка TLS; заголовок `X-Max-Bot-Api-Secret` (если задан
  `secret`); эндпоинт обязан ответить HTTP 200 за 30 сек; экспоненциальные повторы 60с→150с→375с→… (10 попыток за ~8
  часов); при неуспехе 8ч — автоотписка.
- Требования: HTTPS :443, доверенный CA (или Минцифры), без самоподписных, домен = CN/SAN, полная цепочка.
- `secret`: 5–256 символов, `[a-zA-Z0-9_-]`.
- Активная webhook-подписка отключает Long Polling.
- Ответ эндпоинта: HTTP 200 (клиентская часть должна это поддерживать через компонент обработки вебхуков).

### Ключевые схема-соглашения

- Почти все timestamp — **Unix в миллисекундах** (`last_activity_time`, `timestamp`, `last_event_time`), исключение:
  `join_time` — секунды.
- Пагинация — `marker` (int64, nullable) + `count`.
- `message_id` / `callback_id` / `messageId` (path) — строки; `chat_id` / `user_id` — int64.
- Ошибки: `ErrorResponse {code, message, error?}`. HTTP-коды: 400, 401, 404, 405, 429, 503.
- Успех операций: `SuccessResponse {success, message?}`.
- Контакт по кнопке `request_contact`: верификация `hash = HMAC-SHA256(access_token, vcf_info)`, при этом в `vcf_info`
  `\r\n` надо заменить на реальные переносы строк.

### Эндпоинты

| Метод  | Путь                                      | operationId          | Описание                                                                      |
|--------|-------------------------------------------|----------------------|-------------------------------------------------------------------------------|
| GET    | `/me`                                     | `getMe`              | Инфо о боте (BotInfo)                                                         |
| PATCH  | `/me/commands`                            | `editBotCommands`    | Команды бота (макс 32; `[]` — удалить все)                                    |
| GET    | `/chats`                                  | `getChats`           | **DEPRECATED**                                                                |
| GET    | `/chats/{chatId}`                         | `getChat`            | Инфо о чате/канале                                                            |
| PATCH  | `/chats/{chatId}`                         | `editChat`           | title/icon/pin/notify                                                         |
| POST   | `/chats/{chatId}/actions`                 | `sendBotAction`      | SenderAction                                                                  |
| GET    | `/chats/{chatId}/pin`                     | `getPinnedMessage`   | message или null                                                              |
| PUT    | `/chats/{chatId}/pin`                     | `pinMessage`         | body: message_id, notify?                                                     |
| DELETE | `/chats/{chatId}/pin`                     | `unpinMessage`       | Открепление                                                                   |
| GET    | `/chats/{chatId}/members/me`              | `getBotMembership`   | Членство бота (ChatMember)                                                    |
| DELETE | `/chats/{chatId}/members/me`              | `removeBotFromChat`  | Удаление бота                                                                 |
| GET    | `/chats/{chatId}/members/admins`          | `getChatAdmins`      | Список админов + marker                                                       |
| POST   | `/chats/{chatId}/members/admins`          | `addChatAdmin`       | Назначить админа (PUT-семантика)                                              |
| DELETE | `/chats/{chatId}/members/admins/{userId}` | `removeChatAdmin`    | Снять админа                                                                  |
| GET    | `/chats/{chatId}/members`                 | `getChatMembers`     | Участники; query: user_ids?, marker?, count?(1-100, default 20)               |
| POST   | `/chats/{chatId}/members`                 | `addChatMembers`     | body: user_ids (макс 100); ответ + failed_user_ids/details                    |
| DELETE | `/chats/{chatId}/members`                 | `removeChatMember`   | query: user_id (обяз.), block?(bool, default false)                           |
| GET    | `/subscriptions`                          | `getSubscriptions`   | Список webhook-подписок                                                       |
| POST   | `/subscriptions`                          | `createSubscription` | body: url(https), update_types?, secret?                                      |
| DELETE | `/subscriptions`                          | `deleteSubscription` | query: url (обяз.)                                                            |
| GET    | `/updates`                                | `getUpdates`         | Long Polling; query: limit(1-1000, d100), timeout(0-90, d30), marker?, types? |
| POST   | `/uploads`                                | `uploadMedia`        | query: type (обяз.); ответ {url, token?}                                      |
| GET    | `/messages`                               | `getMessages`        | query: chat_id?                                                               | message_ids?(csv), from?, to?, count?(1-100, d50) |
| POST   | `/messages`                               | `sendMessage`        | query: user_id?                                                               | chat_id? (одно из), disable_link_preview?; body: NewMessageBody |
| PUT    | `/messages`                               | `editMessage`        | query: message_id (обяз.); body: NewMessageBody                               |
| DELETE | `/messages`                               | `deleteMessage`      | query: message_id (обяз.)                                                     |
| GET    | `/messages/{messageId}`                   | `getMessageById`     | path: messageId (строка, `[a-zA-Z0-9_-]+`)                                    |
| GET    | `/videos/{videoToken}`                    | `getVideoInfo`       | Инфо о видео (VideoInfo)                                                      |
| POST   | `/answers`                                | `sendAnswer`         | query: callback_id (обяз.); body: {message?: NewMessageBody}                  |

### Enums

- **ChatType**: `chat`, `channel`, `dialog`
- **ChatStatus**: `active`, `removed`, `left`, `closed`
- **SenderAction**: `typing_on`, `sending_photo`, `sending_video`, `sending_audio`, `sending_file`
- **UploadType**: `image`, `video`, `audio`, `file`
- **TextFormat**: `markdown`, `html`
- **ChatAdminPermission**: `read_all_messages`, `add_remove_members`, `add_admins`, `change_chat_info`, `pin_message`,
  `write`, `can_call`, `edit_link`, `edit`, `delete`, `view_stats`
- **UpdateType**: `bot_added`, `bot_started`, `bot_stopped`, `bot_removed`, `chat_title_changed`, `dialog_cleared`,
  `dialog_muted`, `dialog_unmuted`, `dialog_removed`, `message_callback`, `message_created`, `message_edited`,
  `message_removed`, `user_added`, `user_removed`
- **AttachmentType**: `image`, `video`, `audio`, `file`, `sticker`, `inline_keyboard`, `location`, `share`
- **ButtonType**: `callback`, `link`, `request_contact`, `request_geo_location`, `open_app`, `message`, `clipboard`

### Ключевые объекты (DTO)

- `User` (user_id int64, first_name, last_name?, username?, is_bot, last_activity_time, name[deprecated])
- `UserWithPhoto` (+ description?, avatar_url?, full_avatar_url?)
- `BotInfo` = UserWithPhoto + commands?
- `ChatMember` = UserWithPhoto + last_access_time, is_owner, is_admin, join_time, permissions?, alias?
- `Chat` (chat_id, type, status, title?, icon?, last_event_time, participants_count, owner_id?, participants?,
  is_public, link?, description?, dialog_with_user?, messages_count?, pinned_message?)
- `Message` (sender?, recipient, timestamp, link?, body?, stat?, url?)
- `MessageBody` (mid, seq, text?, attachments?, caption?, format)
- `NewMessageBody` (text?, attachments?, link?, notify?, format) — attachments: `null`=без изменений, `[]`=удалить все
- `Attachment` (type, payload?) — payload discriminated по `token`, oneOf из 7 типов payload
- `AttachmentRequest` (type, payload{token?, url?, rows?})
- `InlineKeyboardButton` (type, text, payload?, url?, intent?, app_data?) — макс 210 кнопок / 30 рядов / 7 в ряду (3 для
  link/open_app/request_geo_location/request_contact)
- `Update` (update_type, timestamp, chat_id, user, is_channel?, message?, callback{callback_id, payload?, message}?)
- `Subscription` (url, update_types?)
- `ErrorResponse` (code, message, error?)

## Требования к PHP-клиенту (решения пользователя)

Пользователь хочет:

1. Универсальный, качественный, профессиональный, production-grade клиент.
2. **Best practices** и framework-agnostic архитектуру — ядро можно переиспользовать для модулей под разные фреймворки.
3. Компоненты: клиент API, типизированные модели (DTO), обработка вебхуков (включая верификацию секрета), long polling
   runner, обработка ошибок с типизированными исключениями, ретраи с экспоненциальным бэкоффом (в т.ч.
   `attachment.not.ready`), поддержка загрузки файлов.
4. Перед реализацией — выбор путей реализации делается ПОСЛЕ прочтения плана пользователем (файл `PLAN.md`).

## Ограничения реализации (обязательно соблюдать)

- Не использовать библиотеки без проверки их наличия/оправданности; предпочитать PSR-стандарты (PSR-4,
  PSR-7/PSR-17/PSR-18) там, где это уместно.
- Соблюдать **SOLID**, **DRY**, **KISS** — единая ответственность классов, открытость к расширению, без избыточной
  абстракции и без дублирования логики.
- `composer.json` обязателен (PSR-4, PHP >= 8.4). Код в namespace `GeekCo\MaxPhpClient` (tests — `GeekCo\MaxPhpClient\Tests`).
- Стиль кода: PSR-12; форматирование через php-cs-fixer (`composer run format`), конфиг — `.php-cs-fixer.dist.php`.
- Не добавлять комментарии в код без необходимости.
- Тесты: PHPUnit; HTTP-слой мокать (PSR-18 mock / Guzzle HandlerStack / собственный mock transport).
- Верификация контакта: `hash_equals(hash_hmac('sha256', $normalizedVcf, $accessToken), $hash)`.
- Для фреймворков — отдельные пакеты-мосты (Laravel ServiceProvider / Symfony Bundle) в будущем, ядро не должно зависеть
  от фреймворка.
- **OWASP Top 10**: соблюдать при написании кода — не доверять входящим данным (webhook body, query/path параметры, поля
  из JSON); постоянновременное сравнение секретов (`hash_equals`); не логировать access token, secret, vcf_info,
  callback payload (A02 — cryptographic failures, A09 — security logging); избегать SSRF при обработке `url` из
  вебхуков/подписок (валидация `https://` + домен); ограничение входных данных по размерам и типам (A03 — injection:
  корректное кодирование в PSR-7, никаких конкатенаций в URL); безопасный дефолт — секреты не передавать в открытом
  виде, шифрование/маскирование в логах; для upload-URL строго `https://`.
