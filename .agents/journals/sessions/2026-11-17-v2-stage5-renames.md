---
tags: [ v2, renames, removals, strictness, contact, linked-message, gate ]
date: 2026-11-17
---

## Проблема

Завершить переход на официальную схему: переименовать оставшиеся DTO/методы, удалить legacy-классы и поля, включить
строгость только для подтверждённых смоуком полей.

## Решение

- `User.name` сохранён как обычное nullable-поле (решение 11 C), без `@deprecated`.
- `SuccessResponse`→`SimpleQueryResult` (+`code`), `ErrorResponse`→`Error`,
  `VideoInfo`→`VideoAttachmentDetails`, `EditChatBody`→`ChatPatch` (+`description`),
  `BotCommandsResult`→`BotCommandsInfo`, `ChatMembersResult`→`ChatMembersList`,
  `AddChatMembersResult`→`ModifyMembersResult`, `ChatListResult`→`ChatList`.
- `Callback::$message` удалён; `MessageCallbackUpdate` читает `callback.message` как фолбэк.
- `ContactAttachmentPayload`: `hash: ?string`, `vcfPhone` удалён; `ContactVerifier::verify(..., ?string $hash)` —
  fail-closed на `null`/`''`.
- Удалены `MessageBody.caption`/`format`, `NewMessageLink.chat`, `VideoUrls.mp4`/`preview`, `UploadResult`,
  `getChats()`, `getUpdatesBatch()`, `LocationAttachmentPayload`; `addChatAdmin` без `marker`.
- Методы: `sendBotAction`→`sendChatAction`, `sendAnswer`→`answerCallback`, `getComment`→`getCommentById`,
  `getVideoInfo`→`getVideoAttachmentDetails`.
- `Recipient::$chatType` — типизирован `?ChatType`; `LinkedMessage`/`CommentLinkedMessage`/`NewMessageLink` используют
  `MessageLinkType`, `LinkedMessage` — `sender: ?User` + `senderId: ?int` + legacy `mid`/`chat`/`chatId`.
- Строгость обязательных полей включена только для подтверждённых (`hash`); `Message.body`, `Callback.user`,
  `LinkedMessage.message`, `Subscription.time` остаются tolerant до расширенного смоука (решение 7 B).

## Тесты

Обновлены `DtoCoverageTest`, `AttachmentTest`, `CommentTest`, `WebhookHandlerTest`, `ApiClient*Test`, `JsonTest`,
`LongPollingRunnerTest`, примеры (`send-media`, `inline-keyboard`, `verify-contact`, `echo-handler`), README,
`docs/api-reference.md`, `AGENTS.md`.

## Нюансы

- `ContactAttachmentPayload::$maxInfo` остаётся массивом: в проде бывает частичный профиль.
- `VideoAttachmentDetails` — переименованный `VideoInfo`; метод `getVideoAttachmentDetails` возвращает его.
- `ChatList` пока сохранён как DTO, хотя `getChats()` удалён.
- `Callback.user`/`timestamp` nullable, пока нет смоука callback-апдейтов.

## Gate

Lint 0/167, PHPStan level max 0, PHPUnit 368/903, покрытие 96.28% (2068/2148, методы 95.36%), audit 0. Интеграционный
смоук в этапе 5 не перезапускался; этап 3: 2 passed, 2 skipped по DNS.
