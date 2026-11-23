# План: v2.0.0 — приведение SDK к официальной схеме max-openapi (v0.0.34)

Статус: **завершён по репозиторию: этапы 2–8 выполнены (этап 8 — приёмка на dev-стенде 2026-11-23), этап 9 передан в
релиз — коммит документов, merge `new → main`, тег `v2.0.0`, GitHub Release и Packagist выполняет владелец**. Целевая
версия — **2.0.0** (major, ломающие изменения разрешены, обязателен `docs/migration-v2.md`).

## 0. Коротко

- `max-openapi` приведён к официальной машинной схеме `max-messenger/api-schema` **v0.0.34** (коммит `0016ff3`):
  18 путей, 33 операции, **133 схемы**, `tools/check_spec.py` → 0, зарегистрированных отклонений нет.
- Предыдущий план `2026-09-28-v2-typed-model.md` опирался на `max-openapi@9ef38a6` и официальную схему v0.0.33. Он
  заменён этим планом; историческое исследование и решения, которые остались в силе, сохранены в нём.
- Клиент сейчас: 55 DTO, 10 enum, плоские `Update` / `Attachment` / `AttachmentRequest` / `MarkupElement`, классы
  `ImageAttachmentPayload`, `VideoInfo`, `SuccessResponse`, `ErrorResponse`, `UploadedInfo` с `url`.
- v2 приводит имена и состав DTO к официальной схеме, вводит типизированные иерархии (19 `Update`, 9+9 вложений, 7
  кнопок, 10 элементов разметки), добавляет обёртки и алиасы, удаляет `@deprecated` и легаси-поля.
- План согласован 2026-11-17 (решения 1–11); реализация начата: этап 2 (`Update`/`UpdateList`) выполнен, Gate зелёный.

## 1. Источники и предусловия

| Что               | Значение                                                                                                                                                         |
|-------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Спека             | `geekcodev/max-openapi`, коммит **`a3ae6e7`** (2026-11-16), ветка `new-schema`                                                                                   |
| Официальная схема | `max-messenger/api-schema` **v0.0.34**, коммит `0016ff3`, проверено 2026-11-16                                                                                   |
| Сверка            | `python3 tools/check_spec.py` → код 0; 18 путей = 18, 33 операции = 33, 133 схемы = 133; новых расхождений нет                                                   |
| Предыдущий план   | `.agents/plans/2026-09-28-v2-typed-model.md` (`9ef38a6` / v0.0.33) — заменён этим планом                                                                         |
| База клиента      | `8a4cca9` (merge PR #24), 55 DTO, 10 enum                                                                                                                        |
| Baseline Gate     | 2026-10-20: lint 0/112, PHPStan level max — 0, PHPUnit 389 тестов / 1007 проверок, покрытие 99.82% (1668/1671), audit 0; integration не запускались (без токена) |

**Предусловие по v1.1.11 — закрыто.** v1.1.11 опубликована и задеплоена: тег `v1.1.11` = `8a4cca9` на origin
(merge-коммит `dev → main`), GitHub Release и Packagist выполнены. В v1.1.11 вошли `Attachment::$filename`, толерантный
`hash`, `ChatStatus::Suspended` и остальные аддитивные правки; v2.0.0 стартует с этого состояния. Опубликованные версии
неизменяемы, поэтому v2.0.0 — только новый тег.

## 2. Что изменилось в спеке после `9ef38a6`

### 2.1 Структура

- Схемы: 107 → **133**; имена, поля, `required`, типы, nullability, enum и `operationId` совпадают с официальной схемой.
  Сняты все сознательные отклонения.
- Удалены клиентские/спековые надстройки: `UpdateBase`, `UpdateType`, `AttachmentBase`, `AttachmentRequestBase`,
  `AttachmentType`, `ButtonType`, `InlineKeyboardButton`, `InlineKeyboardButtonRow`.
- Полиморфизм теперь официальный:
    - `Update` — база (`update_type: string`, `timestamp`) + `discriminator` на **19** подтипов;
    - `Attachment` / `AttachmentRequest` — база (`type`) + **9** подтипов каждый;
    - `Button` — база (`type`, `text`) + **7** подтипов;
    - `MarkupElement` — база (`type`, `from`, `length`) + **10** подтипов.
- Метаданные вложений (`filename`, `size`, `width`, `height`, `duration`, `thumbnail`, `transcription`,
  `title`, `description`, `image_url`) перенесены из `payload` на верхний уровень подтипа.
- `VideoAttachmentPayload` и `AudioAttachmentPayload` заменены общим `MediaAttachmentPayload`.
- `InlineKeyboardButton` заменён на `Button` + 7 подтипов; клавиатура — `Keyboard {buttons: array<array<Button>>}`.
- `UploadedInfo` теперь только `{token}` (payload запросов video/audio/file), а ответ `POST /uploads` —
  `UploadEndpoint {url (обяз.), token?}`.
- `SuccessResponse` → `SimpleQueryResult` (+ nullable `code`), `ErrorResponse` → `Error`.
- `User.name`: официальная схема его не содержит, но смоук 2026-11-17 подтвердил наличие в payload (`user`,
  `message.sender`, `max_info`) — это реальный никнейм/отображаемое имя (в т.ч. для бота). Оставить как nullable поле
  **без** `@deprecated`; расхождение со схемой зафиксировать в § 9.
- `Message.body` объявлен обязательным и non-nullable; `Recipient.chat_type` — обязательный `ChatType`.
- `SenderAction` дополнен `mark_seen`.
- `operationId` переименованы (17): `getMe`→`getMyInfo`, `editBotCommands`→`editMyCommands`,
  `getBotMembership`→`getMembership`, `removeBotFromChat`→`leaveChat`, `getChatAdmins`→`getAdmins`,
  `addChatAdmin`→`postAdmins`, `removeChatAdmin`→`deleteAdmins`, `getChatMembers`→`getMembers`,
  `addChatMembers`→`addMembers`, `removeChatMember`→`removeMember`, `createSubscription`→`subscribe`,
  `deleteSubscription`→`unsubscribe`, `uploadMedia`→`getUploadUrl`, `getVideoInfo`→`getVideoAttachmentDetails`,
  `sendBotAction`→`sendAction`, `sendAnswer`→`answerOnCallback`, `getComment`→`getCommentById`.

### 2.2 Переименования, затрагивающие публичный API клиента

| Сейчас                                        | Станет                   | Примечание                                                                                                 |
|-----------------------------------------------|--------------------------|------------------------------------------------------------------------------------------------------------|
| `SuccessResponse`                             | `SimpleQueryResult`      | + `?string $code`; меняются возвраты почти всех mutating-методов                                           |
| `ErrorResponse`                               | `Error`                  | `ApiException::$error`, `getError(): ?Error`, `ResponseDecoder`                                            |
| `VideoInfo`                                   | `VideoAttachmentDetails` | `token`, `width`, `height`, `duration` обязательны                                                         |
| `ImageAttachmentPayload`                      | `PhotoAttachmentPayload` | `photo_id`, `token`, `url` обязательны                                                                     |
| `PhotoAttachmentPayload` (текущий клиентский) | `VideoThumbnail`         | `{url}` + клиентские `width`/`height` сверх спеки                                                          |
| `VideoAttachmentPayload`                      | `MediaAttachmentPayload` | `url`/`token` — строки; метаданные видео — на верхнем уровне                                               |
| `AudioAttachmentPayload`                      | `MediaAttachmentPayload` | общий payload; `transcription` — на верхнем уровне                                                         |
| `InlineKeyboardAttachmentPayload`             | `Keyboard`               | `buttons: list<list<Button>>`                                                                              |
| `InlineKeyboardButton`                        | `Button` + 7 подтипов    | `ButtonType` удаляется                                                                                     |
| `InlineKeyboardButtonRow`                     | —                        | ряды — обычные массивы кнопок                                                                              |
| `UpdatesResult`                               | `UpdateList`             | `updates` + `marker?`                                                                                      |
| `ChatListResult`                              | `ChatList`               | `chats` + `marker?`                                                                                        |
| `ChatMembersResult`                           | `ChatMembersList`        | `members` + `marker?`                                                                                      |
| `AddChatMembersResult`                        | `ModifyMembersResult`    | `success`, `message?`, `code?`, `failed_user_ids?`, `failed_user_details?`                                 |
| `BotCommandsResult`                           | `BotCommandsInfo`        | `commands: list<BotCommand> или null`                                                                      |
| `EditChatBody`                                | `ChatPatch`              | добавляется `description`                                                                                  |
| `UploadedInfo` (ответ)                        | `UploadEndpoint`         | `url` обязателен, `token?`; возврат `uploadMedia()`                                                        |
| `UploadedInfo` (payload запроса)              | `UploadedInfo`           | остаётся только `token`                                                                                    |
| `UploadResult`                                | —                        | удаляется                                                                                                  |
| `LocationAttachmentPayload`                   | —                        | координаты локации — на верхнем уровне вложения                                                            |
| `ChatAdminsResult`                            | остаётся                 | endpoint возвращает `{members: ChatMember[]}`, а схема `ChatAdminsList {admins}` не используется — см. 3.6 |
| `ChatIcon`                                    | остаётся                 | inline-форма `ChatPatch.icon`                                                                              |

### 2.3 Поля и обязательность (сводка)

**Update (19 подтипов, обязательные поля):**

| `update_type`                   | Класс                              | Обязательные поля                                                                |
|---------------------------------|------------------------------------|----------------------------------------------------------------------------------|
| `message_created`               | `MessageCreatedUpdate`             | `message`; `user_locale?`                                                        |
| `message_edited`                | `MessageEditedUpdate`              | `message`                                                                        |
| `message_removed`               | `MessageRemovedUpdate`             | `chat_id`, `message_id`, `user_id`                                               |
| `message_callback`              | `MessageCallbackUpdate`            | `callback`; `message?`, `user_locale?`                                           |
| `comment_created`               | `CommentCreatedUpdate`             | `message` (см. решение 3)                                                        |
| `comment_edited`                | `CommentEditedUpdate`              | `message` (см. решение 3)                                                        |
| `comment_removed`               | `CommentRemovedUpdate`             | `chat_id`, `message_id`, `user_id`, `post_id`                                    |
| `bot_added`                     | `BotAddedToChatUpdate`             | `chat_id`, `user`, `is_channel`                                                  |
| `bot_admin_permissions_changed` | `BotAdminPermissionsChangedUpdate` | `chat_id`, `user_id`, `bot_id`, `is_channel`, `is_admin`; `permissions?`         |
| `bot_removed`                   | `BotRemovedFromChatUpdate`         | `chat_id`, `user`, `is_channel`                                                  |
| `user_added`                    | `UserAddedToChatUpdate`            | `chat_id`, `user`, `is_channel`; `inviter_id?`                                   |
| `user_removed`                  | `UserRemovedFromChatUpdate`        | `chat_id`, `user`, `is_channel`; `admin_id` необязателен, но при наличии — `int` |
| `bot_started`                   | `BotStartedUpdate`                 | `chat_id`, `user`; `payload?`, `user_locale?`                                    |
| `bot_stopped`                   | `BotStoppedUpdate`                 | `chat_id`, `user`; `user_locale?`                                                |
| `chat_title_changed`            | `ChatTitleChangedUpdate`           | `chat_id`, `user`, `title`                                                       |
| `dialog_cleared`                | `DialogClearedUpdate`              | `chat_id`, `user`; `user_locale?`                                                |
| `dialog_muted`                  | `DialogMutedUpdate`                | `chat_id`, `user`, `muted_until`; `user_locale?`                                 |
| `dialog_unmuted`                | `DialogUnmutedUpdate`              | `chat_id`, `user`; `user_locale?`                                                |
| `dialog_removed`                | `DialogRemovedUpdate`              | `chat_id`, `user`; `user_locale?`                                                |

**Вложения ответа (9):**

| `type`            | Класс                      | Поля                                                                                                                                                  |
|-------------------|----------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| `image`           | `PhotoAttachment`          | `payload: PhotoAttachmentPayload`                                                                                                                     |
| `video`           | `VideoAttachment`          | `payload: MediaAttachmentPayload`; `thumbnail?: VideoThumbnail`, `width?`, `height?`, `duration?`                                                     |
| `audio`           | `AudioAttachment`          | `payload: MediaAttachmentPayload`; `transcription?`                                                                                                   |
| `file`            | `FileAttachment`           | `payload: FileAttachmentPayload`; `filename`, `size` по схеме обязательны; в смоуке `size` только в `payload` — читать с фолбэком, strict не включать |
| `sticker`         | `StickerAttachment`        | `payload: StickerAttachmentPayload`; `width`, `height` по схеме обязательны, но в смоуке отсутствуют — nullable/tolerant                              |
| `contact`         | `ContactAttachment`        | `payload: ContactAttachmentPayload`                                                                                                                   |
| `inline_keyboard` | `InlineKeyboardAttachment` | `payload: Keyboard`                                                                                                                                   |
| `share`           | `ShareAttachment`          | `payload: ShareAttachmentPayload`; `title?`, `description?`, `image_url?`                                                                             |
| `location`        | `LocationAttachment`       | `latitude`, `longitude` (оба обязательны), без `payload`                                                                                              |

**Вложения запроса (9):** `PhotoAttachmentRequest` (payload `PhotoAttachmentRequestPayload {token?, url?, photos?}`),
`VideoAttachmentRequest` / `AudioAttachmentRequest` / `FileAttachmentRequest` (payload `UploadedInfo {token}`),
`StickerAttachmentRequest` (`StickerAttachmentRequestPayload {code}`), `ContactAttachmentRequest`
(`ContactAttachmentRequestPayload {name?, contact_id?, vcf_info?, vcf_phone?}`),
`InlineKeyboardAttachmentRequest` (`InlineKeyboardAttachmentRequestPayload {buttons}`),
`ShareAttachmentRequest` (`ShareAttachmentPayload {url?, token?}`), `LocationAttachmentRequest` (`latitude`,
`longitude`).

**Кнопки (7):**

| `type`                 | Класс                      | Обязательные поля                            |
|------------------------|----------------------------|----------------------------------------------|
| `callback`             | `CallbackButton`           | `text`, `payload`                            |
| `link`                 | `LinkButton`               | `text`, `url`                                |
| `request_geo_location` | `RequestGeoLocationButton` | `text`; `quick?`                             |
| `request_contact`      | `RequestContactButton`     | `text`                                       |
| `message`              | `MessageButton`            | `text`                                       |
| `open_app`             | `OpenAppButton`            | `text`, `web_app`; `payload?`, `contact_id?` |
| `clipboard`            | `ClipboardButton`          | `text`, `payload`                            |

**Разметка (10):** `StrongMarkup`, `EmphasizedMarkup`, `UnderlineMarkup`, `StrikethroughMarkup`, `MonospacedMarkup`,
`HighlightedMarkup`, `QuoteMarkup`, `HeadingMarkup` — только `type`/`from`/`length`; `LinkMarkup` + `url` (обязателен);
`UserMentionMarkup` + `user_id?`, `user_link?`. В `discriminator.mapping` ключ `underlined`, а значение enum —
`underline`; фабрика принимает оба, сериализует `underline`.

**Сущности и обёртки:**

- `User`, `UserWithPhoto`, `BotInfo`, `ChatMember`: `name` сохраняется как nullable поле (никнейм/отображаемое имя, не
  deprecated); официальная схема его не содержит — см. 2.1 и § 9; `ChatMember` сохраняет обязательные
  `last_access_time`, `is_owner`, `is_admin`, `join_time`.
- `Message`: `body` обязателен, non-nullable; `sender?`, `recipient`, `timestamp`, `link?`, `stat?`, `url?`.
- `MessageBody`: `mid`, `seq`; `text?`, `markup?: list<MarkupElement>`, `attachments?: list<Attachment>`; **удаляются**
  `caption` и `format`.
- `Recipient`: `chat_type: ChatType` обязателен; `chat_id?`, `user_id?`, `post_id?`.
- `NewMessageLink`: `type: MessageLinkType`, `mid` обязательны; `chat` удаляется (прод-форма старого API).
- `LinkedMessage` / `CommentLinkedMessage`: `type: MessageLinkType`, `message` обязательны; `sender?: User`,
  `chat_id?` (в новой спеке **не обязателен**); `mid` / `chat` / числовой `sender` — прод-формы (см. 3.8).
- `Callback`: `timestamp: int`, `callback_id: string`, `user: User` обязательны; `payload?`; `message` перенесён в
  `MessageCallbackUpdate` (в проде встречается внутри `callback` — см. 3.8).
- `Subscription`: `time: int` обязателен; `update_types?`.
- `ChatPatch`: `title?`, `description?` (новое), `icon?`, `pin?`, `notify?`.
- `FileAttachmentPayload`: `url`, `token` обязательны; `file_size`, `filename`, `size` удаляются (метаданные — на
  `FileAttachment`).
- `PhotoAttachmentPayload`: `photo_id`, `token`, `url` обязательны.
- `MediaAttachmentPayload`: `url`, `token` обязательны.
- `StickerAttachmentPayload`: `url`, `code` обязательны.
- `ShareAttachmentPayload`: `url?`, `token?`.
- `VideoAttachmentDetails`: `token`, `width`, `height`, `duration` обязательны; `urls?`, `thumbnail?`.
- `VideoThumbnail`: `url` обязателен (+ клиентские `width`/`height`).
- `VideoUrls`: `mp4_144`…`mp4_1080`, `hls`; `mp4` и `preview` удаляются.
- `SimpleQueryResult`: `success` обязателен, `message?`, `code?`.
- `Error`: `code`, `message` обязательны, `error?`.
- `ModifyMembersResult`: `success` обязателен, `message?`, `code?`, `failed_user_ids?`, `failed_user_details?`.
- `FailedUserDetails`: `error_code: string`, `user_ids: list<int>` обязательны; легаси-поля `user_id`/`error` — см. 3.8.
- `UploadEndpoint`: `url` обязателен, `token?`; `UploadedInfo`: только `token`.
- Обёртки: `MessageList`, `CommentMessageList`, `ChatList`, `ChatMembersList`, `UpdateList`, `GetSubscriptionsResult`,
  `GetPinnedMessageResult`, `SendMessageResult`, `SendCommentResult`, `UserIdsList`.
- Тела запросов: `ActionRequestBody`, `BotCommandsInfo`, `BotCommandsPatch`, `ChatPatch`, `PinMessageBody`,
  `SubscriptionRequestBody`, `CallbackAnswer`.

## 3. Влияние на клиент по слоям

### 3.1 Enums

| Enum                                                                                  | Действие                                                        |
|---------------------------------------------------------------------------------------|-----------------------------------------------------------------|
| `UpdateType`                                                                          | удалить: тип — строка в `Update`, фабрика по `update_type`      |
| `AttachmentType`                                                                      | удалить: тип — строка, подтипы `Attachment`/`AttachmentRequest` |
| `ButtonType`                                                                          | удалить: подтипы `Button`                                       |
| `SenderAction`                                                                        | добавить `MarkSeen = 'mark_seen'`                               |
| `MessageLinkType`                                                                     | **новый**: `forward`, `reply`; использовать в link-объектах     |
| `ChatType`, `ChatStatus`, `Markup`, `TextFormat`, `UploadType`, `ChatAdminPermission` | без изменений (состав совпадает)                                |

`ChatAdminPermission::isDeprecated()` сохраняется: `post_edit_delete_message`, `edit_message`, `delete_message` в доке
помечены устаревшими, хотя в схеме присутствуют.

### 3.2 `Update` — типизированная иерархия

- `Update` → `abstract readonly` с `update_type: string` и `timestamp: int`; статическая фабрика `fromArray()` по
  `update_type`; неизвестный тип → `InvalidResponseException` (как сейчас для битого `update_type`).
- 19 подтипов в `src/Dto/Update/` (имена — как в спеке).
- `UpdateType` и `Update::$comment` удаляются.
- `WebhookHandler::decode(): Update|list<Update>` — сигнатура сохраняется; `LongPollingRunner` переходит на
  `getUpdates(): UpdateList` (решение 1 — A).
- Удобные плоские поля `$user` / `$chatId` из v1 не сохраняются: в `message_created`/`message_edited` они берутся из
  `message.sender` / `message.recipient`, в `message_callback` — из `callback.user` / `callback.message`. Это осознанная
  потеря эргономики ради соответствия спеке; удобные методы-хелперы, если понадобятся, добавляются отдельно и позже (в
  v2.0.0 их нет).

### 3.3 Вложения, клавиатура, кнопки

- `Attachment` → `abstract readonly` база + 9 подтипов; `Attachment::fromArray()` — фабрика по `type`.
- `AttachmentRequest` → `abstract readonly` база + 9 подтипов; фабрика по `type`.
- `AttachmentPayload` → база `{url}`; `PhotoAttachmentPayload`, `MediaAttachmentPayload`, `FileAttachmentPayload`,
  `ContactAttachmentPayload`, `StickerAttachmentPayload`, `ShareAttachmentPayload`.
- `Keyboard {buttons: list<list<Button>>}`; `InlineKeyboardAttachmentRequestPayload {buttons}`.
- `Button` → `abstract readonly` база + 7 подтипов; `Keyboard::fromArray()` и `Button::fromArray()` — фабрики.
- `LocationAttachment` / `LocationAttachmentRequest` — координаты на верхнем уровне, `payload` нет.
- Метаданные вложений (`filename`, `size`, `width`, `height`, `duration`, `thumbnail`, `transcription`,
  `title`, `description`, `image_url`): читаются с верхнего уровня подтипа, фолбэк — `payload` (прод-форма v1);
  `toArray()` пишет на верхний уровень. Это сохраняет решение 9 прежнего плана и прод-совместимость.
- `MessageBody::$attachments` и `NewMessageBody::$attachments` типизируются как `list<Attachment>` /
  `list<AttachmentRequest>`.

### 3.4 Разметка

- `MarkupElement` → `abstract readonly` база + 10 подтипов; фабрика по `type`; `Markup` enum сохраняется.
- `MessageBody::$markup` и `CommentMessageBody::$markup` → `list<MarkupElement>`.
- `LinkMarkup::$url` обязателен; `UserMentionMarkup` — `?int $userId`, `?string $userLink`.

### 3.5 Методы и возвраты

Решение 1 — **A (утверждено 2026-11-16)**: метод возвращает ровно ту схему ответа, которая объявлена в спецификации;
обёртки не разворачиваются.

Решение 2 — **C (утверждено 2026-11-17)**: семантические имена, `operationId` не копируется. Переименования:
`sendBotAction()` → `sendChatAction()`, `getVideoInfo()` → `getVideoAttachmentDetails()`,
`sendAnswer()` → `answerCallback()`, `getComment()` → `getCommentById()`. Остальные имена сохраняются.

- `getChats()` — **удалить** (в спеке пути нет; deprecated с июня 2026).
- `editChat(ChatPatch $body)` — переименование `EditChatBody`; добавляется `description`.
- `addChatAdmin(...)`: убрать параметр `$marker` и запись `marker` в тело (в спеке поля нет).
- `getChatAdmins()`: параметры `$marker`/`$count` сейчас игнорируются; удалить (HTTP-контракт не меняется).
- `getUpdates(): UpdateList` (marker сохраняется); `getUpdatesBatch()` удаляется как избыточный, **без
  deprecated-алиаса**
  (решение владельца 2026-11-16).
- `getMessages(): MessageList`.
- `sendMessage(): SendMessageResult`.
- `getSubscriptions(): GetSubscriptionsResult`.
- `getPinnedMessage(): GetPinnedMessageResult`.
- `sendComment(): SendCommentResult`.
- `getComments(): CommentMessageList` (уже обёртка).
- `getCommentById(): CommentMessage` (было `getComment`).
- `getChatMembers(): ChatMembersList` (переименование `ChatMembersResult`).
- `getChatAdmins(): ChatAdminsResult` — исключение: endpoint отдаёт `{members}`, схема `ChatAdminsList` — `{admins}`
  (решение 8).
- `uploadMedia(): UploadEndpoint` (вместо `UploadedInfo`).
- `addChatMembers(): ModifyMembersResult`.
- `editBotCommands(): BotCommandsInfo`.
- `getVideoAttachmentDetails(): VideoAttachmentDetails` (было `getVideoInfo`).
- `sendChatAction(): SimpleQueryResult` (было `sendBotAction`).
- `answerCallback(): SimpleQueryResult` (было `sendAnswer`).
- Все методы, возвращавшие `SuccessResponse`, возвращают `SimpleQueryResult`: `sendChatAction`, `pinMessage`,
  `unpinMessage`, `removeBotFromChat`, `addChatAdmin`, `removeChatAdmin`, `createSubscription`, `deleteSubscription`,
  `editMessage`, `deleteMessage`, `answerCallback`, `editComment`, `deleteComment`.
- Полная таблица `метод ↔ operationId` (15 отличий) — в `docs/api-reference.md` § 8.

### 3.6 Сущности и обёртки

- `User.name`: не удалять; сохранить как nullable deprecated-расширение (по смоуку 2026-11-17; ждёт подтверждения);
  обновить цепочки конструкторов без удаления поля.
- `Message::$body` — обязательный `MessageBody`; `CommentMessage::$body` — обязательный `CommentMessageBody`.
- `Recipient::$chatType` — `ChatType`, обязательный; `json`-поле `chat_type`.
- `LinkedMessage` (решение 4 — A): `type: MessageLinkType`; `sender: ?User`; `senderId: ?int` (числовой legacy-поле
  прода); `mid: string` (legacy, фолбэк `message.mid`); `chat: ?string` (`@deprecated`, legacy); `chatId: ?int`;
  `message: ?MessageBody` — обязательность включается в этапе 5 после смоука (решение 7). `toArray()` отдаёт спек-форму
  (`type`, `message`, `sender`, `chat_id`) и legacy `mid`/`chat`; при отсутствии `sender` объектом числовой `senderId`
  сохраняется в `sender` для round-trip.
- `CommentLinkedMessage`: `type: MessageLinkType`, `message: CommentMessageBody` обязательны; `sender: ?User`,
  `chatId: ?int`.
- `NewMessageLink`: `type: MessageLinkType`, `mid` обязателен; `chat` удаляется.
- `Callback`: `timestamp`, `callback_id`, `user` обязательны; `payload?`; `$message` удаляется (решение 5 — A),
  канонический `MessageCallbackUpdate::$message` читается с верхнего уровня с фолбэком `callback.message`.
- `Subscription::$time` — `int` обязательный.
- `ChatPatch` + `description`.
- `FileAttachment`/`StickerAttachment`/`ShareAttachment` — типизированные классы вместо сырого payload.
- `ContactAttachmentPayload`: `hash: ?string` (fail-closed в `ContactVerifier`), `vcfInfo: ?string`, `maxInfo:
  array<string, mixed>|null` (в проде бывает частичным профилем, не `User`); `vcfPhone` удаляется.
  `vcf_phone` остаётся только в `ContactAttachmentRequestPayload` (исходящая форма); телефон извлекается из подписанного
  `vcf_info` через `ContactPhoneExtractor::fromVcf()`.
- `ChatAdminsResult` остаётся с `members: list<ChatMember>` и `marker?`: endpoint `/members/admins` возвращает
  `{members, marker?}`, а схема `ChatAdminsList {admins: ChatAdmin[]}` ни одним путём не используется. Это расхождение
  спеки фиксируется в `docs/api-reference.md` § 9, а не «чинится» переименованием.
- `ChatIcon` остаётся: `ChatPatch.icon` в пути объявлен inline-объектом `{url, payload}`, отдельной схемы нет.

### 3.7 Прод-формы, которые нельзя потерять

| Прод-форма                                                                                         | Решение                                                                                                                                                      |
|----------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `message.link.sender` — числовая строка / int / объект `User`                                      | объект → `LinkedMessage::$sender: User`, число → `LinkedMessage::$senderId: int`; `tolerantInt`; `toArray()` сохраняет числовую форму, если объекта нет      |
| `message.link.mid`, `message.link.chat`                                                            | сохранить как legacy-поля `LinkedMessage`; `mid` с фолбэком `message.mid`, `chat` — `@deprecated`                                                            |
| `callback.message` внутри `Callback`                                                               | `Callback::$message` удаляется; парсер читает `callback.message` как фолбэк и заполняет `MessageCallbackUpdate::$message` (решение 5 — A)                    |
| `ContactAttachmentPayload.vcf_phone` во входящем payload                                           | **удаляется** из входящего DTO; `vcf_phone` остаётся в `ContactAttachmentRequestPayload`; телефон — из подписанного `vcf_info` через `ContactPhoneExtractor` |
| `FailedUserDetails` — прод-форма `{user_id, error}`                                                | читать обе формы; канонические поля `error_code`/`user_ids`, легаси-поля — deprecated                                                                        |
| `Chat.participants` — карта `user_id => активность`                                                | сохранить `Json::intMap()`, `array<int, int>`                                                                                                                |
| `comment_created` / `comment_edited` — фактически `CommentMessage`                                 | типизировать как `CommentMessage`, расхождение со схемой — в § 9 (решение 3)                                                                                 |
| `Message.body` / `Callback.user` / `LinkedMessage.message` в реальных апдейтах могут отсутствовать | включить строгость только после смоука реальных апдейтов (этап 5)                                                                                            |

## 4. Открытые решения (нужно утвердить)

| #  | Вопрос                                                       | Варианты                                                                                                                                                                                                                                                                                                                   | Рекомендация                                                                                                                                                                                                                                                                     |
|----|--------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 1  | Возвраты методов                                             | A — строго по схеме: `SendMessageResult`, `MessageList`, `GetSubscriptionsResult`, `GetPinnedMessageResult`, `UpdateList`, `SendCommentResult`; B — сохранить удобные возвраты (`Message`, `list<Message>`, `list<Subscription>`, `?Message`, `list<Update>`, `CommentMessage`), а обёртки использовать только для разбора | **A — утверждено 2026-11-16**: метод возвращает ровно схему ответа; `uploadMedia(): UploadEndpoint`; `getChatAdmins()` — исключение из-за расхождения спеки (решение 8)                                                                                                          |
| 2  | Имена методов vs `operationId`                               | A — переименовать методы под официальные `operationId`; B — оставить текущие имена; C — семантические имена: переименовать только те, где текущее имя искажает суть                                                                                                                                                        | **C — утверждено 2026-11-17**: `sendBotAction`→`sendChatAction`, `getVideoInfo`→`getVideoAttachmentDetails`, `sendAnswer`→`answerCallback`, `getComment`→`getCommentById`; остальные имена сохраняются; таблица `метод ↔ operationId` (15 отличий) в `docs/api-reference.md` § 8 |
| 3  | Тип `message` у `comment_created` / `comment_edited`         | A — `Message` (как в официальной схеме); B — `CommentMessage` (как в доке и фактическом payload)                                                                                                                                                                                                                           | **B — утверждено 2026-11-17**: `CommentCreatedUpdate::$message` и `CommentEditedUpdate::$message` — `CommentMessage`; расхождение со схемой фиксируется в `docs/api-reference.md` § 9                                                                                            |
| 4  | Легаси `LinkedMessage`                                       | A — `sender: ?User` (спека), `senderId: ?int` (прод), `mid`/`chat` сохранить; B — оставить текущую пару `sender: ?int` + `senderUser: ?User`                                                                                                                                                                               | **A — утверждено 2026-11-17**: `sender` — `User`, `senderId` — числовой legacy, `mid`/`chat` — legacy-поля; `toArray()` сохраняет обе формы                                                                                                                                      |
| 5  | `Callback::$message`                                         | A — удалить, оставить только `MessageCallbackUpdate::$message`; B — сохранить как deprecated-прод-расширение                                                                                                                                                                                                               | **A — утверждено 2026-11-17**: `Callback` без `message`; `MessageCallbackUpdate::$message` заполняется с верхнего уровня, фолбэк — `callback.message`; bridge обновляется на `$update->message`                                                                                  |
| 6  | `ContactAttachmentPayload::$hash`                            | A — `?string` (как в схеме), `ContactVerifier` принимает `null`; B — оставить `string` с `''` (текущее fail-closed поведение v1.1.11)                                                                                                                                                                                      | **A — утверждено 2026-11-17**: `hash: ?string`, `ContactVerifier::verify(..., ?string $hash)` fail-closed; `vcfPhone` удаляется из входящего DTO и остаётся в `ContactAttachmentRequestPayload`; `maxInfo` остаётся массивом                                                     |
| 7  | Строгость `required`                                         | A — строго по схеме сразу; B — строго по схеме, но после смоука реальных апдейтов; сохранить только документированные прод-фолбэки                                                                                                                                                                                         | **B — утверждено 2026-11-17**: сначала реальные payload и матрица присутствия полей, затем строгость. Webhook-подписку по умолчанию не трогать; отзыв — только по согласованию (так и сделано 2026-11-17, подписка восстановлена)                                                |
| 8  | `ChatAdminsResult` / `ChatAdminsList`                        | A — переименовать в `ChatAdminsList {admins}`; B — оставить `ChatAdminsResult {members}`                                                                                                                                                                                                                                   | **B — утверждено 2026-11-17**: endpoint возвращает `{members: ChatMember[], marker?}`, схема `ChatAdminsList {admins}` не используется; `ChatAdmin` остаётся DTO тела запроса                                                                                                    |
| 9  | Удаление enum `UpdateType` / `AttachmentType` / `ButtonType` | A — удалить; B — оставить как удобные справочники значений                                                                                                                                                                                                                                                                 | **A — утверждено 2026-11-17**: в схеме enum нет; типизированные подтипы заменяют их, новые типы добавляются без правки enum                                                                                                                                                      |
| 10 | BC-алиасы старых классов                                     | A — не оставлять, только `docs/migration-v2.md`; B — оставить deprecated-наследники на один релиз                                                                                                                                                                                                                          | **A — утверждено 2026-11-17**: без алиасов; полная таблица «было → стало» и примеры `instanceof` в migration guide                                                                                                                                                               |
| 11 | `User.name` (новый по смоуку)                                | A — удалить по схеме; B — сохранить как deprecated-расширение; C — сохранить как обычное nullable-поле                                                                                                                                                                                                                     | **C — утверждено 2026-11-17**: оставить `?string $name` без `@deprecated` — это реальный никнейм/отображаемое имя из прода (в т.ч. для бота); официальная схема его не содержит, поэтому документировать как прод-расширение в § 9                                               |

## 5. Этапы реализации

Каждый этап — отдельный PR в `dev` с зелёным Gate. Этапы 2–4 можно вести параллельно; этапы 5–8 последовательны.

- **Этап 0 — предусловие (ВЫПОЛНЕН).** v1.1.11 выпущена и задеплоена (тег `v1.1.11` = `8a4cca9` на origin); baseline
  Gate зафиксирован; v2.0.0 начинается с этого состояния.
- **Этап 1 — исследование (ВЫПОЛНЕН 2026-11-16).** Дельта `9ef38a6` → `a3ae6e7`, сверка DTO, этот план.
- **Этап 2 — `Update` — ВЫПОЛНЕН (2026-11-17).** `abstract Update` + 19 подтипов, фабрика, удаление `UpdateType`;
  `UpdateList` вместо `UpdatesResult`, `getUpdates(): UpdateList`, `getUpdatesBatch()` удалён; `WebhookHandler`,
  `LongPollingRunner`, `ApiClient`, примеры и тесты обновлены. Gate: lint 0/130, PHPStan 0, PHPUnit 392/1006, покрытие
  99.84% (1882/1885, методы 99.14%), audit 0; интеграционный read-only смоук 4/4, 1 skipped.
- **Этап 3 — вложения, клавиатура, кнопки — ВЫПОЛНЕН (2026-11-17).** `Attachment`/`AttachmentRequest` + 9+9 подтипов,
  payload-классы (`MediaAttachmentPayload`, `PhotoAttachmentPayload`, `FileAttachmentPayload`,
  `StickerAttachmentPayload`,
  `ShareAttachmentPayload`, `VideoThumbnail`), `Keyboard`, `Button` + 7 подтипов; `MessageBody`/`NewMessageBody` —
  типизированные списки; `AttachmentType`/`ButtonType`/старые плоские классы удалены. `UploadedInfo` стал token-only,
  добавлен `UploadEndpoint`; `uploadMedia()` возвращает `UploadEndpoint`. Gate: lint 0/157, PHPStan 0, PHPUnit 372/938,
  покрытие 96.22% (2011/2090, методы 95.17%), audit 0; integration — 2 passed, 2 skipped (DNS timeout до API).
- **Этап 4 — разметка — ВЫПОЛНЕН (2026-11-17).** `MarkupElement` — abstract readonly-база (`type: Markup`, `from`,
  `length`) + 10 подтипов; `LinkMarkup` (`url`), `UserMentionMarkup` (`user_id?`, `user_link?`); алиас `underlined`
  принимается и нормализуется в `underline`; `MessageBody`/`CommentMessageBody` используют `list<MarkupElement>`. Gate:
  lint 0/167, PHPStan 0, PHPUnit 369/910, покрытие 96.33% (2076/2155, методы 95.40%), audit 0.
- **Этап 5 — сущности, переименования, удаления — ВЫПОЛНЕН (2026-11-17).** `User.name` сохранён (решение 11 C);
  `SuccessResponse`→`SimpleQueryResult` (+`code`), `ErrorResponse`→`Error`, `VideoInfo`→`VideoAttachmentDetails`,
  `EditChatBody`→`ChatPatch` (+`description`), `BotCommandsResult`→`BotCommandsInfo`, `ChatMembersResult`→
  `ChatMembersList`, `AddChatMembersResult`→`ModifyMembersResult`, `ChatListResult`→`ChatList`; `Callback::$message`
  удалён (фолбэк в `MessageCallbackUpdate`), `ContactAttachmentPayload::$hash: ?string`, `vcfPhone` удалён,
  `ContactVerifier::verify(..., ?string $hash)`; `MessageBody.caption`/`format`, `NewMessageLink.chat`,
  `VideoUrls.mp4`/`preview`, `UploadResult`, `getChats()`, `getUpdatesBatch()` удалены; методы переименованы
  (`sendChatAction`, `getVideoAttachmentDetails`, `answerCallback`, `getCommentById`), `addChatAdmin` без `marker`.
  Строгость обязательных полей включена только там, где подтверждено: `hash`; `Message.body`, `Callback.user`,
  `LinkedMessage.message`, `Subscription.time` пока tolerant до расширенного смоука (решение 7 B). Gate: lint 0/167,
  PHPStan 0, PHPUnit 368/903, покрытие 96.28% (2068/2148, методы 95.36%), audit 0.
- **Этап 6 — документация и примеры — ВЫПОЛНЕН (2026-11-17).** Создан `docs/migration-v2.md` (таблицы «было → стало»,
  `instanceof`, обёртки, методы, вложения, кнопки, контакты); обновлены `README.md` (v2-раздел истории, примеры, typed
  Update/вложения/разметка), `docs/api-reference.md` (§ 1, 3, 4, 7, 8 — таблица `метод ↔ operationId`, 9), `AGENTS.md`
  (контракты, счётчики DTO/Enum, gotchas), `examples/` (`echo-handler`, `send-media`,
  `inline-keyboard`, `verify-contact`, `send-to-user`). PHP-код не менялся; Gate этапов 2–5 зелёный.
- **Этап 7 — полный Gate и смоук — ВЫПОЛНЕН (2026-11-17).** Lint 0/167, PHPStan 0, PHPUnit 368/903, покрытие 96.28%
  (2068/2148, методы 95.36%), audit 0; интеграционный read-only смоук 4/4, 1 skipped (`getUpdates` пуст из-за активной
  webhook-подписки). Шаг 7: README, справочник, AGENTS, migration guide и примеры сверены с кодом v2.
- **Этап 8 — предрелизная интеграция на сервере (без выпуска).** Закоммитить ветку `new`; в интеграционном адаптере
  (ветка `new`) перевести зависимость на `dev-new`/path-repository текущего ядра, обновить bridge под typed-модель; в
  тестовом боте обновить bridge и прогнать реальные вебхуки и сценарии: сообщения/редактирование/удаление, callback,
  контакты (hash `null`), вложения/кнопки/разметка, комментарии, группы/каналы, long polling. Найденные расхождения
  фиксировать как регрессии ядра/моста. Только после зелёного сценария — релиз.
- **Этап 9 — релиз после серверной проверки.** `RELEASE_NOTES_v2.0.0.md` → коммит документов в `new` → PR `new → main` →
  тег `v2.0.0` на merge-коммите → GitHub Release → Packagist; затем major-релиз адаптера и обновление Filament-пакетов
  на `^2.0`.

## 6. Тесты и Gate

- Покрытие строк ≥95%; новые классы (≈100 DTO) — с тестами.
- 19 фикстур подтипов `Update`: `instanceof`, неизвестный `update_type`, round-trip `toArray()`.
- По одному примеру каждого из 9 подтипов `Attachment` и 9 `AttachmentRequest`; `location` без `payload`.
- 7 подтипов `Button`, `Keyboard`, 10 подтипов `MarkupElement`; `underlined`/`underline`.
- Обёртки и строгость: обязательные поля, отсутствие необязательных, прод-фолбэки.
- `DtoCoverageTest` — data-provider по списку классов вместо ручных блоков.
- Интеграционные смоук-тесты (read-only): реальные апдейты, комментарии, контакт; без токена — skip.
- BC-контроль: `docs/migration-v2.md` содержит рабочие примеры v2; примеры v1 намеренно не компилируются.

### Смоук для строгости (решение 7)

Цель — не выбирать строгость «на глаз», а получить матрицу фактического присутствия полей. Токен и username бота берутся
из `.env` (`MAX_API_TOKEN`, `MAX_BOT_USERNAME`); секреты, `vcf_info` и payload в отчёт/коммит не попадают.

1. **Read-only проверка доступа:** `getMe()` — подтвердить токен; `getSubscriptions()` — только количество подписок и
   `update_types` (URL и секреты не печатать); `getUpdates(limit: 1, timeout: 0)` — проверить, отдаёт ли long polling
   апдейты при уже существующей webhook-подписке.
2. **Существующую подписку не трогать.** Если `getUpdates()` пуст из-за webhook:
    - использовать shape-логи существующего webhook-приёмника (только ключи полей, без значений);
    - либо, только с явного согласия владельца, создать **временную вторую** подписку на отдельный HTTPS-эндпоинт,
      собрать апдейты и удалить её; существующую не изменять и не удалять.
3. **Собрать матрицу:** 19 типов `Update` и варианты вложений (`image`, `video`, `audio`, `file`, `sticker`, `contact`,
   `inline_keyboard`, `share`, `location`), кнопки и разметка. Для каждого поля — «присутствует всегда / иногда / не
   встречалось».
4. **Включить строгость по матрице:** подтверждённые поля — strict; отсутствующие в реальных payload — остаются nullable
   и фиксируются в `docs/api-reference.md` § 9 как прод-исключения.
5. **Повторный смоук после строгости:** те же сценарии не должны давать `InvalidResponseException`; при регрессии поле
   возвращается в tolerant-режим и документируется.

Минимальный набор сценариев: `message_created`/`message_edited`/`message_removed`/`message_callback`, комментарии,
контакт, стикер, видео/аудио/файл, `bot_started`/`bot_stopped`, `user_added`/`user_removed`, `chat_title_changed`,
`dialog_*`, `bot_admin_permissions_changed`.

**Статус смоука (2026-11-17).** Read-only интеграционный прогон с реальным токеном из `.env` выполнен:
`getMe` и `getSubscriptions` отвечают, `getUpdates` возвращает пустой список — активная webhook-подписка отключает Long
Polling. По согласованию с владельцем подписка тестового стенда (13 типов) была отозвана, собран Long Polling, затем
восстановлена (1 подписка, 13 типов). Backup и shape-логи удалены; существующая подписка снова активна.

Собрано 11 апдейтов: `message_created` ×6 (текст, фото, контакт), `message_edited` ×1, `bot_stopped` ×2,
`bot_started` ×1, `dialog_removed` ×1. Не встретились: `message_removed`, `message_callback`, комментарии, стикер/
видео/аудио/файл/share/location, `user_added`/`user_removed`, `bot_added`/`bot_removed`, `chat_title_changed`,
`dialog_cleared`/`dialog_muted`/`dialog_unmuted`, `bot_admin_permissions_changed`.

Подтверждено в payload: `recipient.chat_type`, `message.body`, верхнеуровневые `user`/`user_locale`, фото (`url`/
`token`/`photo_id`), контакт (`hash`/`vcf_info`/`max_info` — полный профиль), `vcf_phone` отсутствует. Важно: **
`User.name` реально приходит** в `user`, `message.sender` и `max_info`; удалять его нельзя — предлагается оставить как
nullable deprecated-расширение (ждёт подтверждения). `max_info` в этом срезе был полным профилем, но тесты
интеграционные пакеты показывают частичный — поэтому массив остаётся правильным решением.

Строгость: `Message.body` в выборке есть, но forwarded-only не проверен; `Callback.user` и `LinkedMessage.message`
не встретились. Включать strict только для подтверждённых полей, остальные оставить tolerant до следующего смоука.

**Второй смоук (2026-11-17).** 17 апдейтов: `message_created` ×13 (текст, фото, файл, стикер, контакт, локация,
media/share с `url`/`token`, сообщение со ссылкой), `message_removed` ×1, `dialog_muted` ×1, `dialog_unmuted` ×1,
`dialog_cleared` ×1. Не встретились: `message_callback`, `user_added`/`user_removed`, `bot_added`/`bot_removed`,
`chat_title_changed`, комментарии, `bot_admin_permissions_changed`.

Новые факты:

- **`FileAttachment`:** `filename` есть и на верхнем уровне, и в `payload`; `size` — только в `payload` (в нашем срезе).
  Требовать top-level `size` нельзя; читать с фолбэком, strict не включать.
- **`StickerAttachment`:** payload `code`/`url`, а `width`/`height` на верхнем уровне **отсутствуют**. Оставить
  nullable/толерантно, strict не включать.
- **`ContactAttachmentPayload`:** в одном контакте `hash` отсутствовал; `max_info` — полный профиль; `vcf_phone`
  отсутствует. Это подтверждает `hash: ?string` и удаление `vcfPhone`.
- **`LinkedMessage`:** `sender` пришёл объектом `User`, при этом `mid`, `chat` и `chat_id` присутствуют — решение 4 A
  (legacy-поля + `senderId`) подтверждено.
- **`LocationAttachment`:** координаты на верхнем уровне, `payload` отсутствует.
- **`User.name`** снова присутствует.
- **`Message.body`** присутствует; forwarded-only по-прежнему не проверен.

`message_callback`, `user_*`, `bot_added/removed`, `chat_title_changed`, комментарии и `bot_admin_permissions_changed`
нужно добрать отдельным webhook-срезом или следующей сессией.

**Отзыв webhook-подписки.** По документации MAX после `DELETE /subscriptions` webhook-уведомления прекращаются, и Long
Polling становится доступным. Это рабочий, но разрушительный путь: production перестаёт получать события на время
смоука; `secret` не возвращается `GET /subscriptions`, поэтому для восстановления нужны сохранённые URL,
`update_types` и секрет; события за паузу могут быть потеряны; `bot_admin_permissions_changed` через Long Polling не
доставляется. Применять только в согласованное окно и с немедленным восстановлением подписки; предпочтительнее
shape-логи существующего приёмника или отдельный тестовый бот.

**Тестовый бот (2026-11-17).** Токен из `.env` принадлежит тестовому боту. В локальном `.env`
проекта `MAX_WEBHOOK_ENABLED=false`, в `.env.prod` — `true`; секрет подписки в prod-конфиге есть. Long Polling для
тестового бота достаточен для сбора полей почти всех типов; исключение — `bot_admin_permissions_changed` (только
webhook). Отзыв подписки на тестовом боте допустим, но только по чек-листу с восстановлением URL/`update_types`/секрета.

Gate целиком: `composer run lint` → 0; `vendor/bin/phpstan analyse` → 0; `vendor/bin/phpunit` → зелёные;
`composer run coverage` → ≥95%; `composer audit` → 0; шаг 7 — сверка документации.

## 7. Риски и нюансы

1. **Строгость `required` — главный риск вебхука.** `Message.body`, `Callback.user`, `LinkedMessage.message` в реальных
   апдейтах могут отсутствовать; включать после смоука, а не «по схеме».
2. **Спека vs прод.** `comment_created` (схема `Message`, факт `CommentMessage`), `callback.message`,
   `ChatAdminsList {admins}` vs endpoint `{members}` — фиксировать в § 9, не «чинить» код по схеме. Удаление
   `ContactAttachmentPayload::$vcfPhone` требует обновить интеграционный адаптер и пакет: телефон берётся только из
   подписанного `vcf_info`; если MAX начнёт присылать `vcf_phone` во входящем payload, он будет проигнорирован — это
   осознанный security-выбор.
3. **`readonly`-наследование.** База и подтипы `readonly`, инициализация только в декларирующем классе; фабрики — в
   базе, не в подтипах.
4. **Именованные аргументы.** Переименование параметров конструкторов ломает named arguments у потребителей; в миграции
   перечислить все конструкторы.
5. **`toArray()`.** Метаданные вложений теперь на верхнем уровне; `buttons` вместо `rows`; `UploadEndpoint` вместо
   `UploadedInfo` — проверить round-trip.
6. **Рост классов.** 55 DTO → ≈100; `DtoCoverageTest` перевести на data-provider, иначе тест станет хрупким.
7. **Мосты.** Интеграционные адаптеры и пакеты используют плоский `Update` и старые имена; обновление мостов — отдельные
   задачи после v2.0.0.
8. **Публикация.** Опубликованные версии неизменяемы; v2.0.0 — только новый тег; `version` в `composer.json` не
   указывать.

## 8. Definition of Done

- [x] Имена и состав DTO совпадают с официальной схемой v0.0.34; все отступления — в `docs/api-reference.md` § 9.
- [x] `Update` (19), `Attachment`/`AttachmentRequest` (9+9), `Button` (7), `MarkupElement` (10) — типизированные
  иерархии с фабриками.
- [x] Методы возвращают схемы ответов (решение 1 — A): `SendMessageResult`, `MessageList`, `GetSubscriptionsResult`,
  `GetPinnedMessageResult`, `UpdateList`, `SendCommentResult`, `ChatMembersList`, `UploadEndpoint`.
- [x] Публичные имена методов — семантические (решение 2 — C): `sendChatAction`, `getVideoAttachmentDetails`,
  `answerCallback`, `getCommentById`; остальные сохранены; `docs/api-reference.md` § 8 содержит таблицу
  `метод ↔ operationId`.
- [x] `UpdateType`, `AttachmentType`, `ButtonType`, `InlineKeyboardButton*`, `UploadResult`,
  `LocationAttachmentPayload`,
  `VideoInfo`, `SuccessResponse`, `ErrorResponse`, `getChats()`, `getUpdatesBatch()` — удалены/переименованы по плану.
- [x] `ContactAttachmentPayload::$hash` — `?string`; `ContactVerifier::verify` принимает `null` и fail-closed;
  `vcfPhone` удалён из входящего DTO, `maxInfo` остаётся массивом.
- [x] `docs/migration-v2.md`, `README.md`, `docs/api-reference.md`, `AGENTS.md`, `examples/` обновлены.
- [x] Gate зелёный: lint 0, PHPStan 0, PHPUnit зелёные, покрытие ≥95%, audit 0; шаг 7 пройден.
- [ ] `RELEASE_NOTES_v2.0.0.md`; merge `dev → main`; тег `v2.0.0`; GitHub Release; Packagist.
- [ ] Мосты обновлены на `^2.0` (отдельные задачи).

## 9. Статус

- [x] Спека изучена, дельта `9ef38a6` → `a3ae6e7` снята, DTO сверены (2026-11-16).
- [x] План актуализирован; предыдущий план помечен заменённым.
- [x] Предусловие v1.1.11 закрыто: версия опубликована и задеплоена.
- [x] Решение 1 (возвраты методов) утверждено: вариант **A**.
- [x] Решение 2 (имена методов) утверждено: вариант **C** — четыре семантических переименования, остальные имена
  сохраняются, таблица `метод ↔ operationId`.
- [x] Решение 3 (тип комментариев в событиях) утверждено: вариант **B** — `CommentMessage`.
- [x] Решение 4 (легаси `LinkedMessage`) утверждено: вариант **A** — `sender: User`, `senderId: int`, `mid`/`chat`
  legacy.
- [x] Решение 5 (`Callback::$message`) утверждено: вариант **A** — поле удаляется, сообщение переносится в
  `MessageCallbackUpdate::$message` с фолбэком `callback.message`.
- [x] Решение 6 (`hash` и `vcf_phone`) утверждено: вариант **A** — `hash: ?string`, nullable-верификатор,
  `vcfPhone` удаляется из входящего DTO; `maxInfo` остаётся массивом.
- [x] Решение 7 (строгость `required`) утверждено: вариант **B** — сначала смоук реальных payload и матрица присутствия
  полей, затем включение строгости; webhook-подписку по умолчанию не трогать (отзыв — по согласованию, выполнен
  2026-11-17, подписка восстановлена).
- [x] Решение 8 (`ChatAdminsResult` / `ChatAdminsList`) утверждено: вариант **B** — оставить
  `ChatAdminsResult {members}`.
- [x] Решение 9 (удаление enum `UpdateType` / `AttachmentType` / `ButtonType`) утверждено: вариант **A**.
- [x] Решение 10 (BC-алиасы) утверждено: вариант **A** — без алиасов, только `docs/migration-v2.md`.
- [x] Решение 11 (`User.name`) утверждено: вариант **C** — оставить `?string $name` без `@deprecated` как
  прод-расширение/никнейм.
- [x] Все решения 1–11 утверждены владельцем (2026-11-17); план готов к реализации.
- [x] Этап 2 (`Update` + 19 подтипов, `UpdateList`) реализован, Gate зелёный.
- [x] Этап 3 (вложения, клавиатура, кнопки) реализован, Gate зелёный.
- [x] Этап 4 (разметка) реализован, Gate зелёный.
- [x] Этап 5 (сущности, переименования, удаления) реализован, Gate зелёный; остаточная строгость — после расширенного
  смоука.
- [x] Этап 6 (документация и примеры) выполнен.
- [x] Этап 7 (финальный Gate и смоук в репозитории) выполнен; релиз не выпускался.
- [x] Аудит по официальному навыку `max-messenger` (max-agents-skill) выполнен 2026-11-17: внедрены проверки long
  polling (300 мс), `callback_id`, лимита 4000 символов; результаты — в
  `.agents/plans/2026-11-17-max-messenger-skill-audit.md`, Gate повторно зелёный.
- [ ] Отложенные клиентские валидации из аудита (лимиты клавиатуры, взаимоисключение `url`/`token`/`photos`, минимум
  один ряд inline-клавиатуры, per-chat лимит `/answers`) — отдельная задача после серверного смоука; список — в разделе
  «Отложено (не забыть)» того же файла.
- [x] Этап 8 (предрелизная интеграция в адаптере и тестовом боте): приёмка на dev-стенде пройдена 2026-11-23 — вебхуки,
  вложения, согласие, lifecycle-события, long polling.
- [x] Этап 9 (релиз после серверной проверки): release notes v2.0.0 и статус AGENTS подготовлены; 2026-11-23 владелец
  коммитит документы и выпускает тег `v2.0.0` (merge `new → main`, GitHub Release, Packagist).
