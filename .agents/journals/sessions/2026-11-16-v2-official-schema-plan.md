---
tags: [ openapi, spec-sync, official-schema, v2, plan, research, dto, attachments, update, buttons, markup ]
date: 2026-11-16
---

## Проблема

`max-openapi` приведён к официальной машинной схеме `max-messenger/api-schema` v0.0.34 (коммит `0016ff3`), имена и
структура схем совпадают с официальными: 133 схемы, `Update`/`Attachment`/`AttachmentRequest`/`Button`/`MarkupElement` —
базы с `discriminator` и подтипами, метаданные вложений на верхнем уровне, общий `MediaAttachmentPayload`, `Keyboard`,
`SimpleQueryResult`/`Error`, `UploadEndpoint`/`UploadedInfo`, `User.name` удалён. Прежний план v2.0.0 опирался на
`max-openapi@9ef38a6` и v0.0.33 и стал неактуальным.

## Решение

Составлен новый план `.agents/plans/2026-11-16-v2-official-schema.md`; старый
`.agents/plans/2026-09-28-v2-typed-model.md` помечен заменённым с сохранением истории. В плане:

- дельта `9ef38a6` → `a3ae6e7` и 17 переименований `operationId`;
- таблицы «было → стало» по DTO и полям, 19 `Update`, 9+9 вложений, 7 кнопок, 10 разметки;
- этапы 0–8 (предусловие v1.1.11 закрыто → типизация → строгость/удаления → документация → Gate → релиз);
- 10 решений с рекомендациями (возвраты методов, имена методов, тип комментариев, легаси `LinkedMessage`,
  `Callback.message`, `hash`, строгость `required`, `ChatAdminsResult`, удаление enum, BC-алиасы); решение 1
  (возвраты методов) утверждено владельцем — вариант **A**, методы возвращают схемы ответов; `getUpdatesBatch()`
  удаляется без deprecated-алиаса, `getUpdates(): UpdateList` сохраняет `marker`; решение 2 (имена методов) —
  вариант **C**: `sendBotAction`→`sendChatAction`, `getVideoInfo`→`getVideoAttachmentDetails`,
  `sendAnswer`→`answerCallback`, `getComment`→`getCommentById`, остальные имена сохраняются, таблица
  `метод ↔ operationId` в справочнике; решение 3 (тип комментариев в событиях) — вариант **B**: `CommentMessage`,
  расхождение со схемой — в § 9; решение 4 (легаси `LinkedMessage`) — вариант **A**: `sender` — `User`,
  `senderId` — числовой legacy, `mid`/`chat` сохранены; решение 5 (`Callback::$message`) — вариант **A**: поле
  удаляется, сообщение — в `MessageCallbackUpdate::$message` с фолбэком `callback.message`; решение 6 (`hash` и
  `vcf_phone`) — вариант **A**: `hash: ?string`, nullable-верификатор, `vcfPhone` удаляется из входящего DTO,
  `maxInfo` остаётся массивом; решение 7 (строгость `required`) — вариант **B**: смоук реальных payload и матрица
  присутствия полей до включения строгости; webhook-подписку по умолчанию не трогать, отзыв — по согласованию (так
  и сделано, подписка восстановлена); решение 8 (`ChatAdminsResult`) — вариант **B**: оставить `members`, схема
  `ChatAdminsList {admins}` не используется; решение 9 (enum) — вариант **A**: удалить `UpdateType`/`AttachmentType`/
  `ButtonType`; решение 10 (BC-алиасы) — вариант **A**: без алиасов, только migration guide; решение 11 (`User.name`) —
  вариант **C**: оставить `?string` без `@deprecated`.
  как никнейм/прод-расширение;
- прод-расхождения для § 9: `comment_created` → `CommentMessage`, `callback.message`, `ChatAdminsList {admins}` vs
  endpoint `{members}`, `vcf_phone`.

План согласован по всем решениям 1–11 (2026-11-17); PHP-код не менялся, реализация — по отдельной команде.

По уточнению владельца исправлен статус v1.1.11: версия **готова, опубликована и задеплоена** (тег `v1.1.11` =
`8a4cca9` на origin, GitHub Release и Packagist). Обновлены `AGENTS.md`, `RELEASE_NOTES_v1.1.11.md`, планы
v1.1.11/v1.1.10 и журнал; команда определения версии в `AGENTS.md` заменена на `sort -V`, потому что
`git ls-remote --tags origin | tail -1` даёт лексикографический `v1.1.9` и врёт на `v1.1.10`/`v1.1.11`.

## Тесты

Не запускались: изменений в `src/`, `tests/`, `examples/` нет.

## Нюансы

- `LinkedMessage.chat_id` в новой спеке **не обязателен** (обязательны `type` и `message`) — прежний план считал иначе.
- `Message.body` и `Callback.user` объявлены обязательными, но в реальных апдейтах могут отсутствовать — строгость
  включать после смоука.
- Спека `CommentCreatedUpdate.message` = `Message`, при этом `CommentMessage` и текстовая дока описывают комментарий;
  рекомендация — `CommentMessage` + запись в § 9.

## Gate

Полный Gate не запускался: PHP-код не менялся. Шаг 7 (сверка документации) выполнен частично — обновлены планы, статус
v1.1.11 в `AGENTS.md` и release notes; README и справочник будут правиться на этапах 5–6 после утверждения.

Интеграционный read-only смоук прогнан 2026-11-17 с реальным токеном из `.env`: 4 теста, 4 проверки, 1 skipped
(`it_reads_messages_of_the_last_dialog` — «No updates available to derive a chat_id»). `getUpdates` пуст из-за активной
webhook-подписки; существующая подписка не изменялась. Токен и payload в отчёт не выводились.

Long Polling смоук: подписка тестового стенда (13 типов) отозвана и восстановлена; собрано 11 апдейтов —
`message_created` ×6 (текст, фото, контакт), `message_edited` ×1, `bot_stopped` ×2, `bot_started` ×1,
`dialog_removed` ×1. В payload подтверждён `User.name` — сохранён как обычное nullable-поле без `@deprecated`
(решение 11); `vcf_phone` отсутствует, `max_info` пришёл полным профилем. Backup и shape-логи удалены.

Второй Long Polling смоук: 17 апдейтов — `message_created` ×13 (текст, фото, файл, стикер, контакт, локация,
media/share, link), `message_removed` ×1, `dialog_muted` ×1, `dialog_unmuted` ×1, `dialog_cleared` ×1. Найдено:
`FileAttachment.size` только в `payload` (strict top-level нельзя), `StickerAttachment.width/height` отсутствуют
(nullable), контакт без `hash` (подтверждает `?string`), `LinkedMessage` с объектом `sender` + `mid`/`chat`/`chat_id`
(решение 4 A), `LocationAttachment` без `payload`, `User.name` снова присутствует. Подписка восстановлена (1).
