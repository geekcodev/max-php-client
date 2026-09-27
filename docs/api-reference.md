# Справочник API MAX

> Источник истины по контрактам **MAX Messenger Bot API**: спецификация, эндпоинты, enums, DTO, лимиты, вебхуки.
> Читай **целиком** перед любыми правками DTO, enum, транспорта, вебхуков или вызовов API. Для правил работы
> разработчиков и ИИ-агентов смотри `../AGENTS.md`.

Спецификация: **https://github.com/geekcodev/max-openapi** (OpenAPI 3.1.0). Сервер: **https://platform-api2.max.ru**.

## 1. Процесс обновления при изменениях спеки

При новом коммите в `max-openapi` — синхронизировать ядро по шагам:

1. Скачать свежую спеку и сгенерировать дифф против последнего разобранного коммита:
   `git clone https://github.com/geekcodev/max-openapi /tmp/opencode/max-openapi` → `git diff` двух коммитов.
2. Пройтись по диффу: расхождения «спека vs код» → правки в `src/`/`tests/` + документация → обязательный Gate
   (`AGENTS.md`, раздел 8).
3. Отложенное проверять реальным API через интеграционный смоук (`--group integration`, токен `MAX_API_TOKEN`), а не
   выдумывать payload/формат.
4. **Проверено и покрыто кодом (при новых диффах НЕ трогать повторно):**
   `ErrorResponse.code` — string; инлайн-клавиатура `payload.buttons`; `web_app`/`contact_id`; contact-вложение
   (`ContactAttachmentPayload`); nullable `User.last_activity_time`/`Update.user`; `getMessages` `from`/`to`;
   `Attachment` без discriminator (маппинг по `type`); `NewMessageBody` без tg-специфики; deprecated-права
   администраторов (см. раздел 8); `join_time` в миллисекундах; rate limits 30 rps + 2 req/s (раздел 5).
5. Версионирование — только git-тегами; правки каждой синхронизации описывать в
   `.agents/release/RELEASE_NOTES_vX.Y.Z.md`.

## 2. Аутентификация

- Заголовок `Authorization: <access_token>` — **без** `Bearer ` префикса, токен голым.
- Передача токена через query-параметры **не поддерживается**.

## 3. Критичные оговорки

- Домен **`platform-api2.max.ru`** (НЕ `platform-api.max.ru`).
- Нужен сертификат Минцифры в доверенных (для локальных сред — кастомный CA).
- HTTP-вебхуки не поддерживаются — только HTTPS.
- Long Polling ограничен по скорости и хранению событий — **не для production**.
- `GET /chats` **deprecated с июня 2026** — подписка на `bot_added`/`bot_started` + хранение chat_id у себя.
- `type=photo` deprecated → `type=image`.

## 4. Загрузка медиа (`POST /uploads`)

- `type` — **query-параметром**; ответ: `{url, token}`.
- Домены: `file` → `https://fu.oneme.ru`, `image` → `https://iu.oneme.ru`, `video`/`audio` →
  `https://vu.okcdn.ru`.
- Лимиты: image 50 МБ или 7680×7680 px; video 250 МБ; audio 256 МБ или 60 мин; file 4 ГБ.
- После загрузки **ждать перед отправкой** сообщения, иначе `attachment.not.ready` — ретрай с экспоненциальной
  задержкой.

## 5. Rate limits

- **Глобальный лимит: 30 rps** на `platform-api2.max.ru` (все запросы).
- Отправка/редактирование/удаление сообщений и ответы на callback: **макс. 2/сек на диалог/чат/канал**.
- Клиент применяет локально оба лимита: глобальный token bucket 30 req/s (ожидание в `HttpClient`, настраивается опцией
  `global_rate_limiter` в `ApiClient::create()`) и per-chat 2 req/s (`RateLimiter`, исключение
  `RateLimitException` при исчерпании).

## 6. Вебхуки (`POST /subscriptions`)

- Модель: событие → POST на webhook с объектом `Update`; проверка TLS; заголовок `X-Max-Bot-Api-Secret`
  (если задан `secret`); эндпоинт обязан ответить HTTP 200 за 30 сек; повторы 60с→150с→375с→… (10 попыток за ~8 часов);
  при неуспехе 8 ч — автоотписка.
- Требования: HTTPS :443, доверенный CA (или Минцифры), без самоподписных, домен = CN/SAN, полная цепочка.
- `secret`: 5–256 символов, `[a-zA-Z0-9_-]`.
- Активная webhook-подписка отключает Long Polling.

## 7. Ключевые схема-соглашения

- Все timestamp — **Unix в миллисекундах** (`last_activity_time`, `timestamp`, `last_event_time`, `join_time`).
- Пагинация — `marker` (int64, nullable) + `count`.
- `message_id` / `callback_id` / `messageId` (path) — строки; `chat_id` / `user_id` — int64.
- Ошибки: `ErrorResponse {code, message, error?}`. HTTP-коды: 400, 401, 404, 405, 429, 503.
- Успех операций: `SuccessResponse {success, message?}`.
- Контакт по кнопке `request_contact`: `hash = HMAC-SHA256(access_token, vcf_info)`; в `vcf_info` `\r\n`
  заменять на реальные переносы строк.

## 8. Эндпоинты

| Метод  | Путь                                      | operationId          | Описание                                                                                                                  |
|--------|-------------------------------------------|----------------------|---------------------------------------------------------------------------------------------------------------------------|
| GET    | `/me`                                     | `getMe`              | Инфо о боте (BotInfo)                                                                                                     |
| PATCH  | `/me/commands`                            | `editBotCommands`    | Команды бота (макс 32; `[]` — удалить все)                                                                                |
| GET    | `/chats`                                  | `getChats`           | **DEPRECATED**                                                                                                            |
| GET    | `/chats/{chatId}`                         | `getChat`            | Инфо о чате/канале                                                                                                        |
| PATCH  | `/chats/{chatId}`                         | `editChat`           | title/icon/pin/notify                                                                                                     |
| POST   | `/chats/{chatId}/actions`                 | `sendBotAction`      | SenderAction                                                                                                              |
| GET    | `/chats/{chatId}/pin`                     | `getPinnedMessage`   | message или null                                                                                                          |
| PUT    | `/chats/{chatId}/pin`                     | `pinMessage`         | body: message_id, notify?                                                                                                 |
| DELETE | `/chats/{chatId}/pin`                     | `unpinMessage`       | Открепление                                                                                                               |
| GET    | `/chats/{chatId}/members/me`              | `getBotMembership`   | Членство бота (ChatMember)                                                                                                |
| DELETE | `/chats/{chatId}/members/me`              | `removeBotFromChat`  | Удаление бота                                                                                                             |
| GET    | `/chats/{chatId}/members/admins`          | `getChatAdmins`      | Список админов + marker                                                                                                   |
| POST   | `/chats/{chatId}/members/admins`          | `addChatAdmin`       | Назначить админа (PUT-семантика)                                                                                          |
| DELETE | `/chats/{chatId}/members/admins/{userId}` | `removeChatAdmin`    | Снять админа                                                                                                              |
| GET    | `/chats/{chatId}/members`                 | `getChatMembers`     | Участники; query: user_ids?, marker?, count?(1-100, default 20)                                                           |
| POST   | `/chats/{chatId}/members`                 | `addChatMembers`     | body: user_ids (макс 100); ответ + failed_user_ids/details                                                                |
| DELETE | `/chats/{chatId}/members`                 | `removeChatMember`   | query: user_id (обяз.), block?(bool, default false)                                                                       |
| GET    | `/subscriptions`                          | `getSubscriptions`   | Список webhook-подписок                                                                                                   |
| POST   | `/subscriptions`                          | `createSubscription` | body: url(https), update_types?, secret?                                                                                  |
| DELETE | `/subscriptions`                          | `deleteSubscription` | query: url (обяз.)                                                                                                        |
| GET    | `/updates`                                | `getUpdates`         | Long Polling; query: limit(1-1000, d100), timeout(0-90, d30), marker?, types?                                             |
| POST   | `/uploads`                                | `uploadMedia`        | query: type (обяз.); ответ {url, token?}                                                                                  |
| GET    | `/messages`                               | `getMessages`        | query: chat_id? message_ids?(csv), from?, to?, count?(1-100, d50)                                                         |
| POST   | `/messages`                               | `sendMessage`        | query: user_id? chat_id? (одно из), disable_link_preview?; body: NewMessageBody                                           |
| PUT    | `/messages`                               | `editMessage`        | query: message_id (обяз.); body: NewMessageBody                                                                           |
| DELETE | `/messages`                               | `deleteMessage`      | query: message_id (обяз.)                                                                                                 |
| GET    | `/messages/{messageId}`                   | `getMessageById`     | path: messageId (строка, `[a-zA-Z0-9_-]+`)                                                                                |
| GET    | `/videos/{videoToken}`                    | `getVideoInfo`       | Инфо о видео (VideoInfo)                                                                                                  |
| POST   | `/answers`                                | `sendAnswer`         | query: callback_id (обяз.); body: {message?: NewMessageBody, notification?: string} (message или notification обязателен) |

### Enums

- **ChatType**: `chat`, `channel`, `dialog`
- **ChatStatus**: `active`, `removed`, `left`, `closed`
- **SenderAction**: `typing_on`, `sending_photo`, `sending_video`, `sending_audio`, `sending_file`
- **UploadType**: `image`, `video`, `audio`, `file`
- **TextFormat**: `markdown`, `html`
- **ChatAdminPermission**: `read_all_messages`, `add_remove_members`, `add_admins`, `change_chat_info`,
  `pin_message`, `write`, `can_call`, `edit_link`, `edit`, `delete`, `view_stats`; deprecated (только в ответе API, не
  выдавать): `post_edit_delete_message`, `edit_message`, `delete_message`
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
- `Update` (update_type, timestamp, chat_id, user|null, is_channel?, message?, callback{callback_id, payload?,
  message}?, user_locale?, title?, payload?, muted_until?, message_id?, user_id?, inviter_id?, admin_id?)
- `Subscription` (url, update_types?)
- `ErrorResponse` (code, message, error?)

## 9. Что делать при расхождении «спека vs реальный API»

Прод-поведение важнее спеки, когда речь о типах значений. Зафиксированные случаи (не перепроверять без причины):

| Случай                                          | Что приходит                                                    | Как обработано в коде                                                     |
|-------------------------------------------------|-----------------------------------------------------------------|---------------------------------------------------------------------------|
| `message.link.sender` объявлен `int64`          | строка `"277570130"` (и варианты: float, пробелы)               | `Json::tolerantInt()`; `LinkedMessage::$sender` — `?int` (v1.1.4, v1.1.5) |
| `request_contact`: `hash` в hex                 | base64, стандартный и URL-safe, с паддингом и без               | `ContactVerifier` принимает обе формы (v1.1.2)                            |
| `vcf_info` с экранированными переводами строк   | после `json_decode` — реальные CRLF + перевод после `END:VCARD` | `ContactVerifier` хэширует raw-байты (v1.1.3)                             |
| `join_time`, `last_activity_time`               | Unix в миллисекундах                                            | без деления на 1000 (проверено)                                           |
| `attachment.not.ready`                          | ошибка 400 сразу после загрузки                                 | ретрай с экспоненциальной задержкой                                       |
| `sendAnswer` без `message` и без `notification` | ошибка API                                                      | fail-fast на клиенте                                                      |
| Шаг 2 загрузки фото                             | токен внутри `photos`-словаря                                   | извлечение токена из `photos`                                             |

Каждое такое расхождение фиксируется в `.agents/journals/sessions/` и в `.agents/release/RELEASE_NOTES_vX.Y.Z.md`.
