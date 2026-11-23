---
tags: [ v2, update, update-list, typed-dto, breaking, gate ]
date: 2026-11-17
---

## Проблема

По плану v2.0.0 (`.agents/plans/2026-11-16-v2-official-schema.md`, решения 1–11) нужно перейти от плоского `Update`
и `UpdateType` к официальной типизированной иерархии: 19 подтипов, `update_type` строкой, `UpdateList` вместо
`UpdatesResult`, без `getUpdatesBatch()`.

## Решение

- `Dto\Update` — `abstract readonly` база (`updateType: string`, `timestamp: int`), фабрика `fromArray()` по
  `update_type`; неизвестный тип → `InvalidResponseException`, отсутствующий — прежняя ошибка поля.
- 19 подтипов в `src/Dto/Update/`: `MessageCreated/Edited/Removed/Callback`, `CommentCreated/Edited/Removed`,
  `BotAdded/BotAdminPermissionsChanged/BotRemoved`, `UserAdded/UserRemoved`, `BotStarted/Stopped`,
  `ChatTitleChanged`, `DialogCleared/Muted/Unmuted/Removed`.
- `CommentCreatedUpdate::$message` / `CommentEditedUpdate::$message` — `CommentMessage` (решение 3 B);
  `MessageCallbackUpdate` читает `message` с верхнего уровня с фолбэком `callback.message`.
- `Dto\UpdateList` (`updates`, `marker?`) заменил `UpdatesResult`; `ApiClient::getUpdates()` возвращает `UpdateList`;
  `getUpdatesBatch()` удалён (решение 1 A); `LongPollingRunner` переведён на `getUpdates()`.
- Удалён `Enum\UpdateType` (решение 9 A). Поля `User.name` пока сохранены; `Callback::$message`, `hash`, вложения,
  `SuccessResponse`, `ButtonType` и остальные этапы 3–8 не трогались.
- Обновлены `WebhookHandlerTest`, `ApiClient*Test`, `LongPollingRunnerTest`, `DtoCoverageTest`, `DtoTest`, `JsonTest`,
  `Integration/SmokeTest`, `examples/echo-handler.php`, `examples/verify-contact.php`, документация.

## Тесты

Добавлены 19 тестов подтипов + фабрика/неизвестный тип/ошибки прав; `UpdateList` в `DtoCoverageTest`; обновлены long
polling и webhook-тесты. `composer run lint` — 0/130; `phpstan` — 0; `phpunit` — 392/1006; покрытие 99.84% (1882/1885,
методы 99.14%); `composer audit` — 0 после обновления `symfony/filesystem` до v8.1.11; интеграционный read-only смоук —
4/4, 1 skipped (Long Polling пуст из-за активной webhook-подписки).

## Нюансы

- `Update` не хранит удобные плоские `user`/`chatId`: для `message_*` они берутся из `message`, для callback — из
  `callback.user`/`message`; это соответствует плану.
- `MessageCallbackUpdate::toArray()` пока может отдавать `callback.message` и верхнеуровневый `message` (удаление
  `Callback::$message` — этап 5).
- `User.name` оставлен без `@deprecated` (решение 11 C), `maxInfo` — массив (решение 6).
- Строгость `required` пока не включалась: `Message.body`, `Callback.user`, `LinkedMessage.message` остаются nullable до
  этапа 5.

## Gate

Lint 0/130, PHPStan level max 0, PHPUnit 392/1006, покрытие 99.84% (1882/1885, методы 99.14%), audit 0. Интеграционный
смоук read-only 4/4, 1 skipped. Шаг 7: обновлены AGENTS/README/docs § 8 для `Update`/`UpdateList`; остальные
v2-разделы — этапы 3–6.
