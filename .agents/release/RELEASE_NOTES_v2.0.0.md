# v2.0.0

Синхронизация с официальной схемой MAX (`max-messenger/api-schema` v0.0.34) и переход на типизированную модель событий.
Версия ломающая: плоский `Update` и enum-типы удалены, методы и DTO переименованы. Миграция — `docs/migration-v2.md`.

## Новое

- `Update` — abstract-база (`updateType`, `timestamp`) и 19 типизированных подтипов (`MessageCreatedUpdate`,
  `MessageEditedUpdate`, `MessageCallbackUpdate`, `CommentCreatedUpdate`, `BotAddedToChatUpdate`, …). Поля события
  находятся в подтипе; плоской модели больше нет.
- `Attachment`/`AttachmentRequest` — abstract-базы с 9+9 подтипами; payload-классы `MediaAttachmentPayload`,
  `PhotoAttachmentPayload`, `FileAttachmentPayload`, `StickerAttachmentPayload`, `ShareAttachmentPayload`,
  `VideoThumbnail`. Метаданные файла (`filename`, `size`) — на `FileAttachment`.
- `Button` — база и 7 подтипов (`CallbackButton`, `LinkButton`, `RequestContactButton`, `RequestGeoLocationButton`,
  `OpenAppButton`, `MessageButton`, `ClipboardButton`), `Keyboard` вместо `InlineKeyboardAttachmentPayload`.
- `MarkupElement` — база и 10 подтипов; разметка ответа — `list<MarkupElement>`.
- Обёртки ответов: `SimpleQueryResult`, `Error`, `UpdateList`, `ChatMembersList`, `ModifyMembersResult`,
  `ChatAdminsResult`, `ChatList` (удалён), `ChatPatch`, `BotCommandsInfo`, `VideoAttachmentDetails`, `UploadEndpoint`.
- Клиентские проверки до запроса: текст сообщения и комментария ≤ 4000 символов, непустой `callback_id`, интервал long
  polling ≥ 300 мс.
- В `docs/api-reference.md` зафиксированы правила сочетания вложений и отступления продакшена от схемы.

## Изменение (BC)

- Удалены `UpdateType`, `AttachmentType`, `ButtonType`, `getChats()`, `getUpdatesBatch()`, `UploadResult`,
  `VideoInfo`, `SuccessResponse`, `ErrorResponse`, `InlineKeyboardButton*`, `ImageAttachmentPayload`,
  `VideoAttachmentPayload`, `AudioAttachmentPayload`, `LocationAttachmentPayload`, `InlineKeyboardAttachmentPayload`,
  `AddChatMembersResult`, `ChatMembersResult`, `BotCommandsResult`, `EditChatBody`, `ChatListResult`, `UpdatesResult`.
- Переименованы методы: `sendBotAction` → `sendChatAction`, `sendAnswer` → `answerCallback`,
  `getComment` → `getCommentById`, `getVideoInfo` → `getVideoAttachmentDetails`.
- `getUpdates()` возвращает `UpdateList`; итерация — по `$batch->updates`.
- `Callback::$message` удалён: сообщение нажатой кнопки — в `MessageCallbackUpdate::$message` (с фолбэком
  `callback.message`).
- `ContactAttachmentPayload::$hash` — `?string`, `vcfPhone` удалён: номер берётся только из `vcf_info`
  (`ContactPhoneExtractor`), проверка подписи fail-closed.
- `MessageBody::$caption`/`$format` удалены: текст и `markup` только в разметке ответа.
- `LinkedMessage::$sender` — `?User`, `$senderId` — `?int` (обе прод-формы), легаси `mid`/`chat` сохранены.
- `Chat::$participants` — карта `array<int, int>` (с v1.1.9).
- BC-алиасов нет: переход — по `docs/migration-v2.md`.

## Затронутые сценарии

- Вебхук: ответ может быть объектом или списком; разбор типизированный, неизвестный `update_type` отклоняется с
  `InvalidResponseException` (400 на вебхуке). Обязательные поля событий (`chat_id`, `user` для lifecycle, `message`,
  `callback`) проверяются на разборе.
- Long polling: `getUpdates()` возвращает `UpdateList`, пауза между запросами не меньше 300 мс.
- Загрузка медиа: `uploadMedia()` возвращает `UploadEndpoint` (`url`, `token`); для видео/аудио/файла в запросе
  используется `UploadedInfo` (token).
- Комментарии: `getComments`/`sendComment`/`editComment`/`deleteComment`/`getCommentById`, события
  `comment_created`/`comment_edited`/`comment_removed` типизированы.
- Контакт без `hash`: апдейт разбирается, подпись не проходит, телефон не считается подтверждённым.

## Совместимость

- PHP ^8.4, PSR-18/17, без рантайм-зависимостей от фреймворков.
- Опубликованные версии v1.x остаются доступными; переход на v2 — отдельным обновлением зависимости.

## Качество

Lint 0/167, PHPStan level max 0, PHPUnit 372/372 (907 assertions), покрытие 96.29% строк (2075/2155),
`composer audit` 0; интеграционный read-only смоук 4/4 (1 skipped: long polling отключён активной webhook-подпиской).
