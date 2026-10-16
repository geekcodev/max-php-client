---
tags: [ openapi, spec-sync, comments, markup, updates, deprecations, dto, docs, gate ]
date: 2026-09-28
---

# Сессия: синхронизация с max-openapi@745aaf2 (релиз v1.1.6)

## Проблема

Новая редакция спецификации `max-openapi` (коммит `745aaf2`, 2026-09-27) добавила 3 операции (итого 32) — API
комментариев к постам в каналах, разобранную разметку `markup`, `UploadedInfo` как ответ `/uploads`, 4 новых типа
обновлений и 19 типизированных подтипов `Update` и вложений; часть существующих полей убрана или переопределена. Клиент
отставал.

## Решение

Релиз v1.1.6 без ломающих изменений (решения владельца от 2026-09-28, план
`.agents/plans/2026-09-27-openapi-sync.md`):

- **Комментарии**: пять методов `ApiClient` и шесть DTO. `comment_id` (= `mid`) в `editComment`/`deleteComment` — query,
  не путь; идентификаторы валидируются регуляркой до запроса. `sendComment()` и `sendAnswer()` принимают
  `disable_link_preview`.
- **Разметка**: enum `Markup` (10 значений) и плоский `MarkupElement` — отдельные подклассы отложены до v2.0.0, полей
  достаточно для всех подтипов спеки.
- **События**: `UpdateType` до 19 значений, в `Update` добавлены `postId`, `comment`, `botId`, `isAdmin`, `permissions`.
  Плоский `Update` сохранён; `comment_created`/`comment_edited` разбираются в `$comment`, `toArray()` отдаёт их полем
  `message`, как в API.
- **`LinkedMessage`**: принята новая форма спеки (`message`, `sender` как `User`, `chat_id`) с сохранением прежних
  полей — это закрывает продолжение регрессий v1.1.4 и v1.1.5.
- **Вложения**: координаты локации с верхнего уровня и из вложенного payload, `code`/`latitude`/`longitude` в
  `AttachmentRequest`, `photo_id` в `ImageAttachmentPayload`.
- **Устарело до v2.0.0**: `getChats()`, `addChatMembers()`, `MessageBody::$caption`/`$format`, `NewMessageLink::$chat`,
  `UploadResult` (предок `UploadedInfo`).

## Тесты

330 тестов, 872 проверки. Новые: `tests/ApiClientCommentsTest.php`, `tests/Dto/CommentTest.php`,
`tests/Dto/MarkupElementTest.php`, `tests/Dto/AttachmentTest.php`; расширены `tests/Dto/UpdateTest.php`,
`tests/DtoCoverageTest.php`, `tests/ApiClientEndpointsTest.php`. Покрытие строк 99.81% (порог 95%); непокрыты только
defensive-ветки `Internal\Json::describe()` и `WebAppDataValidator`, существовавшие до сессии.

## Нюансы

- В `MarkupElement.discriminator.mapping` спеки опечатка `underlined` вместо `underline` — клиент следует enum.
- `AttachmentType` в спеке содержит 9 значений, включая `contact` (в ранних заметках сессии он был пропущен).
- Три предположения в тестах ошиблись и были исправлены по факту кода: имена полей `BotCommand` (`name`, не `command`),
  тексты исключений `Json::float`/`Json::enum`/`listFromField`, отсутствие `payload` в `AttachmentRequest::toArray()`
  при пустом payload.
- Интеграционные смоук-тесты (группа `integration`) не прогонялись: нужен `MAX_API_TOKEN` и запуск с `--network host`.

## Gate

- `composer run lint` — 0 замечаний (после `composer run format` для `src/Dto/SendCommentResult.php`).
- `vendor/bin/phpstan analyse` — 0 ошибок (level max).
- `vendor/bin/phpunit` — 330 тестов, 872 проверки, зелёные.
- `composer run coverage` — 99.81% строк при пороге 95%.
- `composer audit` — 0 уязвимых пакетов («No security vulnerability advisories found»). Шаг выполнялся через
  `docker run --network host`: из сети `docker compose run` запрос к packagist обрывается по таймауту (curl error 28).
