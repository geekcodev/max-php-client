# План: синхронизация клиента со спецификацией max-openapi (745aaf2)

Статус: завершён. Целевая версия: **v1.1.6**.

## Цель

Привести клиент в соответствие с новой редакцией спецификации `max-openapi`: добавить API комментариев и разобранную
разметку (`markup`), расширить плоский `Update` и DTO вложений, исправить расхождения в существующих DTO. Типизированные
подтипы обновлений и вложений (19 и 9 соответственно) отложены до v2.0.0, чтобы не ломать публичный API.
Прод-особенности MAX (CRLF в `vcf_info`, base64/hex contact hash, миллисекундные timestamp,
`attachment.not.ready`) сохраняются.

Релиз v1.1.6 — **без ломающих изменений**: публичный API расширяется, устаревшее помечается `@deprecated` и удаляется в
v2.0.0. Решения владельца проекта от 2026-09-28 зафиксированы в таблице «Решения» и в разделе «Реализация»; открытых
вопросов по BC больше нет.

## Решения

| Вопрос                                       | Решение                                                                                          |
|----------------------------------------------|--------------------------------------------------------------------------------------------------|
| Версия                                       | **v1.1.6** (v1.1.4 и v1.1.5 выпущены 2026-09-27: теги на `origin/main`)                          |
| Типизированные `Update`                      | плоская модель сохраняется и расширяется; 19 подтипов — **в v2.0.0**                             |
| `getChats()` и `addChatMembers()`            | оставить, пометить `@deprecated`, удалить в v2.0.0                                               |
| `MessageBody.caption` и `MessageBody.format` | оставить, пометить `@deprecated`, удалить в v2.0.0; в `NewMessageBody` `format` остаётся рабочим |

## Исследование

### Источник истины

| Что               | Значение                                                                                              |
|-------------------|-------------------------------------------------------------------------------------------------------|
| Репозиторий спеки | `https://github.com/geekcodev/max-openapi`, клон в `/tmp/opencode/max-openapi`                        |
| Новая редакция    | коммит `745aaf2` от 2026-09-27                                                                        |
| Baseline          | `1beb879` (непосредственный родитель; предыдущая синхронизация клиента — около `195c923`, 2026-08-14) |
| Официальная схема | `max-messenger/api-schema` v0.0.33, синхронизирована 2026-09-27                                       |
| Объём диффа       | 5 файлов, +3799 / −994 строк; `openapi.yaml` — 4304 строки                                            |
| Операций          | было 29, стало 32                                                                                     |

### Эндпоинты

Удалены из спеки:

| Операция         | Путь                           | Решение                                                                                          |
|------------------|--------------------------------|--------------------------------------------------------------------------------------------------|
| `getChats`       | `GET /chats`                   | оставить метод, пометить `@deprecated`: чат-ид хранится по подписке на `bot_added`/`bot_started` |
| `addChatMembers` | `POST /chats/{chatId}/members` | оставить метод, пометить `@deprecated`: добавление участников недоступно боту                    |

Добавлены (5 операций, тег `Comments`):

| Операция        | Путь                                             | Параметры                                 | Ответ                |
|-----------------|--------------------------------------------------|-------------------------------------------|----------------------|
| `getComments`   | `GET /messages/{messageId}/comments`             | `comment_ids`, `after`, `before`, `count` | `CommentMessageList` |
| `sendComment`   | `POST /messages/{messageId}/comments`            | `disable_link_preview`                    | `SendCommentResult`  |
| `editComment`   | `PUT /messages/{messageId}/comments`             | `comment_id` (query, обязательный)        | `SuccessResponse`    |
| `deleteComment` | `DELETE /messages/{messageId}/comments`          | `comment_id` (query, обязательный)        | `SuccessResponse`    |
| `getComment`    | `GET /messages/{messageId}/comments/{commentId}` | `commentId` (path)                        | `CommentMessage`     |

Обратить внимание: `comment_id` у `editComment` и `deleteComment` передаётся в query, а не в путь. Значение
`comment_id` — это `mid` комментария.

Изменены существующие:

| Операция         | Изменение                                                               |
|------------------|-------------------------------------------------------------------------|
| `sendAnswer`     | добавлен query `disable_link_preview` (август 2026, changelog Messages) |
| `editChat`       | `nullable: true` → `type: [object, null]` — косметика, поведение то же  |
| `getChatMembers` | то же косметическое переписывание nullable                              |

### Enums

`UpdateType` — добавлены 4 значения: `comment_created`, `comment_edited`, `comment_removed`,
`bot_admin_permissions_changed` (итого 19). `AttachmentType` без изменений (9 значений), но в спеке появилось объяснение
`contact` и требование «должно быть единственным вложением в сообщении».

### Схемы

Новые:

- Комментарии: `Comment`, `NewCommentBody`, `CommentMessage`, `CommentMessageBody`, `CommentMessageList`,
  `CommentLinkedMessage`, `SendCommentResult`.
- Разметка: `Markup` (enum), `MarkupElement` + 10 подтипов (`StrongMarkup`, `EmphasizedMarkup`, `UnderlineMarkup`,
  `StrikethroughMarkup`, `MonospacedMarkup`, `HighlightedMarkup`, `LinkMarkup`, `QuoteMarkup`, `HeadingMarkup`,
  `UserMentionMarkup`).
- Типизированные обновления: 19 подтипов `*Update` поверх `UpdateBase`.
- Типизированные вложения: `AttachmentRequest` (9 подтипов) + `AttachmentRequestBase` и 9 подтипов запросов;
  `PhotoAttachmentRequest(+Payload)`, `VideoAttachmentRequest(+Payload)`, `AudioAttachmentRequest(+Payload)`,
  `FileAttachmentRequest(+Payload)`, `StickerAttachmentRequest(+Payload)`, `ContactAttachmentRequest(+Payload)`,
  `InlineKeyboardAttachmentRequest(+Payload)`, `ShareAttachmentRequest(+Payload)`, `LocationAttachmentRequest`.
- Прочее: `UploadedInfo` (ответ `/uploads`), `VideoThumbnail`, `Image`, `Chat`, `ChatAdmin`, `BotCommand`.

Изменены:

| Схема                  | Что изменилось                                                                                     |
|------------------------|----------------------------------------------------------------------------------------------------|
| `MessageBody`          | убраны `caption` и `format`, добавлен `markup: list<MarkupElement>`                                |
| `LinkedMessage`        | `sender` теперь объект `User`, а не int64                                                          |
| `MessageStat.views`    | стал обязательным                                                                                  |
| `Message`              | без изменений по полям (`sender` уже был `User`); `stat` уточнён как «только для постов в каналах» |
| `NewMessageLink`       | `chat` больше нет; остаются `type` и `mid`                                                         |
| `Recipient`            | `post_id` и `chat_type` стали обязательными                                                        |
| `MessageStat`          | `views` стал обязательным                                                                          |
| `Attachment`           | разбит на 9 typed-подтипов; `contact` переехал в enum-ветку с обязательным payload                 |
| `BotCommand`           | `description` теперь опционально (раньше спека ошибочно требовала оба поля)                        |
| `/uploads`             | ответ — `UploadedInfo` (`url` обязателен, `token` nullable) вместо плоского объекта                |
| `InlineKeyboardButton` | добавлено `contact_id` (альтернатива `web_app` для `open_app`)                                     |

Удалены: `FailedUserDetails`, `ImageAttachmentPayload`, `LocationAttachmentPayload`.

### Ошибки и особенности спеки, которые нельзя копировать как есть

1. `MarkupElement.discriminator.mapping` содержит ключ `underlined`, тогда как enum использует `underline`. Реализация
   следует enum (`underline`) — иначе `UnderlineMarkup` не резолвится.
2. Комментарии — только для сообщений в каналах, у бота должно быть право `read_all_messages`.
3. `sendComment`/`editComment` принимают `NewCommentBody` с `text` (до 4000 символов), `format` и `link`; вложений нет.
4. `LinkedMessage.sender` в спеке — `User`, но в проде приходит и объект `{"user_id":7}`, и числовая строка. Нужна
   толерантная модель (см. «Реализация», п. 4).
5. `CommentMessage.sender` nullable — бывает `null`, если комментарий опубликован от имени канала.
6. Описание `disable_link_preview` в официальной `schema.yaml` противоречит смыслу параметра; спека права (`true` —
   превью выключено), клиент следует спеке.
7. Разметка в `MessageBody` — это разобранная структура (`from` + `length` + тип), а не markdown-строка.

## Реализация

1. **Комментарии (новое)**: `CommentMessage`, `CommentMessageBody`, `CommentLinkedMessage`, `CommentMessageList`,
   `SendCommentResult`, `NewCommentBody`; методы `ApiClient::getComments()` (с фильтрами), `sendComment()`,
   `editComment()`, `deleteComment()`, `getComment()`.
2. **Разметка**: enum `Markup` (10 значений по спеке) и плоский `MarkupElement` с полями `type`, `from`, `length`,
   `url` (только `link`), `user_id` и `user_link` (только `user_mention`) — этого достаточно для всех 10 подтипов спеки,
   отдельные подклассы отложены до v2.0.0; поле `markup` в `MessageBody`; `caption` и `format` помечаются
   `@deprecated` и остаются (BC), `toArray()` продолжает их отдавать.
3. **Обновления**: 4 новых значения в `UpdateType`; новые поля в плоском `Update` — `postId`, `botId`, `isAdmin`,
   `permissions` (`list<ChatAdminPermission>`). Плоская модель сохраняется (BC), 19 подтипов в v2.0.0.
4. **`LinkedMessage`**: сохранить `?int $sender` (BC), добавить `?User $senderUser`; `fromArray()` принимает
   `User`-объект, числовую строку и int, заполняя оба поля. Это чинит прод-регрессию v1.1.5 без лома.
5. **`LinkedMessage`/`MessageStat`**: см. п. 4. `MessageStat` без изменений по составу (уточнено `views` как
   обязательное). `Message` без изменений.
6. **Вложения**: `Attachment` остаётся плоским (discriminated по `AttachmentType`, 9 значений) и получает координаты
   локации на верхнем уровне с tolerant-разбором вложенной формы; `AttachmentRequest` зеркалит набор полей и получает
   `code` для стикера и `latitude`/`longitude` для локации. `ImageAttachmentPayload` дополнен `photo_id` (спека называет
   подтип `PhotoAttachmentPayload`), `LocationAttachmentPayload` остаётся парсером вложенной формы. Разбиение на
   подклассы — v2.0.0.
7. **Мелкие правки**: `Recipient.postId`, `NewMessageLink.chat` и `MessageBody.caption`/`format` — `@deprecated`,
   `BotCommand.description` nullable, `ChatAdminPermission::fromValue()` вместо private-парсеров в `ChatAdmin` и
   `ChatMember`, `UploadResult` → `UploadedInfo` (наследник, BC), `sendAnswer` и `sendComment` с
   `disable_link_preview`, `getChats()` и `addChatMembers()` — `@deprecated` вместе с `AddChatMembersResult` и
   `FailedUserDetails`. `MessageStat.views` и `InlineKeyboardButton.contactId` уже соответствовали спеке.
8. **Тесты**: `tests/Dto/CommentTest.php`, `tests/Dto/MarkupElementTest.php`, `tests/Dto/AttachmentTest.php`,
   `tests/ApiClientCommentsTest.php`; расширены `tests/Dto/UpdateTest.php`, `tests/DtoCoverageTest.php`,
   `tests/ApiClientEndpointsTest.php`. Покрытие линий 99.81% при пороге 95%.
9. **Документация**: `docs/api-reference.md` (таблица эндпоинтов, enums, DTO, раздел 9 — новая запись про
   `link.sender`), `README.md` (раздел «История изменений», пример комментариев), `AGENTS.md` (gotcha про разметку и
   `comment_id` в query).
10. **Gate**: `composer run lint`, `vendor/bin/phpstan analyse`, `vendor/bin/phpunit`, `composer run coverage`,
    `composer audit`.

## Нюансы

- Публичный API: `?int $sender` и плоский `Update` не трогаем — расширяем. Новые методы добавляются необязательными
  параметрами в конец.
- `getChats` и `addChatMembers` удалены из спеки, но в проде ещё работают — методы остаются с `@deprecated` до v2.0.0.
- `MessageBody::markup` — массив объектов, а не строка; `NewMessageBody` остаётся с `format` (форматирование при
  отправке), `MessageBody` (ответ) — с `markup`.
- `User` в `LinkedMessage` — тот же DTO, что и в `Update::$user`, переиспользование, не дублирование.

## Тесты

- 330 тестов, 872 проверки — зелёные; покрытие линий 99.81% (порог 95%).
- Непокрытыми остались только defensive-ветки, существовавшие до синхронизации: `Internal\Json::describe()` при
  несериализуемом значении и две ветки `WebAppDataValidator`.
- Не выполнено: интеграционные смоук-тесты реального API (группа `integration`) — нужен `MAX_API_TOKEN` и
  `--network host`.

## Статус

завершён
