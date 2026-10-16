# v1.1.6 — синхронизация со спецификацией `max-openapi@745aaf2`

Спецификация: https://github.com/geekcodev/max-openapi, редакция от 2026-09-27 (32 операции вместо 29, `openapi.yaml`
4304 строки). Релиз обратно совместим: публичные сигнатуры не менялись, устаревшее помечено `@deprecated` и удаляется в
v2.0.0.

## Новое: комментарии

Пять операций с тегом `Comments`, тело комментария без вложений, текст до 4000 символов:

| Метод             | HTTP                                             | Идентификатор комментария                         |
|-------------------|--------------------------------------------------|---------------------------------------------------|
| `getComments()`   | `GET /messages/{messageId}/comments`             | фильтры `comment_ids`, `after`, `before`, `count` |
| `sendComment()`   | `POST /messages/{messageId}/comments`            | ответ `{message: CommentMessage}`                 |
| `editComment()`   | `PUT /messages/{messageId}/comments`             | query `comment_id`                                |
| `deleteComment()` | `DELETE /messages/{messageId}/comments`          | query `comment_id`                                |
| `getComment()`    | `GET /messages/{messageId}/comments/{commentId}` | path `commentId`                                  |

- `comment_id` — это `mid` комментария, передаётся **query-параметром** (не в пути); `message_id` и `comment_id`
  валидируются на `[a-zA-Z0-9_-]+` до запроса.
- `sendComment()` принимает `disable_link_preview`, `sendAnswer()` тоже теперь его поддерживает.
- DTO: `CommentMessage`, `CommentMessageBody`, `CommentLinkedMessage`, `CommentMessageList`, `SendCommentResult`,
  `NewCommentBody`.
- Требуется право `read_all_messages` в канале; отдельного per-chat лимита для комментариев в спеке нет, действует
  глобальный 30 rps.
- Апдейты `comment_created` и `comment_edited` разбираются в `Update::$comment` (`CommentMessage`), `Update::$message`
  остаётся `null`; `Update::toArray()` отдаёт комментарий полем `message`, как в ответе API. У `comment_removed`
  доступны
  `message_id` и `post_id`.

## Новое: разобранная разметка ответов

- Enum `Markup` (10 значений спеки): `strong`, `emphasized`, `underline`, `strikethrough`, `monospaced`, `highlighted`,
  `link`, `quote`, `heading`, `user_mention`.
- DTO `MarkupElement`: `type`, `from`, `length`, `url` (только `link`), `user_id` и `user_link` (только `user_mention`).
- Поле `markup` добавлено в `MessageBody` и `CommentMessageBody`.
- Известная опечатка спеки: в `MarkupElement.discriminator.mapping` ключ `underlined` вместо `underline`. Клиент следует
  enum и принимает `underline`; значение `underlined` отклоняется как неизвестное.

## Обновления событий: 19 типов

- `UpdateType` пополнился значениями `comment_created`, `comment_edited`, `comment_removed`,
  `bot_admin_permissions_changed` (итого 19).
- В `Update` добавлены поля `postId`, `comment`, `botId`, `isAdmin`, `permissions` (`list<ChatAdminPermission>`, разбор
  через `ChatAdminPermission::fromValue()`).
- Плоская модель `Update` сохранена: типизированные подтипы (19 схем `*Update`) отложены до v2.0.0.

## Исправления и уточнения

- `LinkedMessage` понимает новую форму спеки: вложенный `message` (`MessageBody`), `sender` как объект `User`
  (`$senderUser`) и `chat_id` (`$chatId`); прежние `?int $sender`, `mid` и `chat` работают как раньше. Это продолжение
  регрессий v1.1.4 и v1.1.5 с `message.link.sender`.
- Вложения: координаты локации читаются с верхнего уровня вложения и из вложенного `payload`;
  `AttachmentRequest` получил `code` (стикер) и `latitude`/`longitude` (локация); `ImageAttachmentPayload` дополнен
  `photo_id` (в спеке подтип называется `PhotoAttachmentPayload`).
- `Recipient` получил `postId`; `BotCommand::$description` стал необязательным (спека ошибочно требовала оба поля).
- `ChatAdmin` и `ChatMember` больше не дублируют разбор прав — используется `ChatAdminPermission::fromValue()`.
- Ответ `POST /uploads` — `UploadedInfo` (`url` обязателен, `token` nullable); `UploadResult` оставлен предком для BC.
- `getMessageById()` переиспользует общий валидатор идентификаторов вместе с комментариями.

## Устарело (удаление в v2.0.0)

| Что                                                                        | Причина                                                               |
|----------------------------------------------------------------------------|-----------------------------------------------------------------------|
| `ApiClient::getChats()`                                                    | нет в спеке с июня 2026; chat_id хранить по `bot_added`/`bot_started` |
| `ApiClient::addChatMembers()`, `AddChatMembersResult`, `FailedUserDetails` | добавление участников убрано из спеки, боту недоступно                |
| `MessageBody::$caption`, `MessageBody::$format`                            | убраны из спецификации, форматирование ответа приходит в `markup`     |
| `NewMessageLink::$chat`                                                    | убрано из спецификации                                                |
| `UploadResult`                                                             | заменён на `UploadedInfo`                                             |

`NewMessageBody::$format` (форматирование при отправке) остаётся рабочим полем.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer) — 0 замечаний; PHPStan level max — 0 ошибок.
- PHPUnit: 330 тестов, 872 проверки — зелёные; покрытие строк 99.81% при пороге 95%.
- Новые тесты: `tests/ApiClientCommentsTest.php`, `tests/Dto/CommentTest.php`, `tests/Dto/MarkupElementTest.php`,
  `tests/Dto/AttachmentTest.php`; расширены `tests/Dto/UpdateTest.php`, `tests/DtoCoverageTest.php`,
  `tests/ApiClientEndpointsTest.php`.
- Интеграционные смоук-тесты реального API не прогонялись: нужен `MAX_API_TOKEN` и `--network host`.
