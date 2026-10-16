# v1.0.4 — Сверка с реальной документацией MAX

Релиз сверен с актуальной документацией dev.max.ru (POST/PUT /messages, PATCH /chats, POST /chats/{id}/members, объект
User, вложения, long polling). Исправлены критические расхождения между спекацией, клиентом и реальным API.

## Критические исправления (отправка/приём сообщений)

- `ApiClient::sendMessage()`: разворачивает ответ `POST /messages` — реальный API возвращает `{message: {...}}`. Ранее
  клиент парсил «голый» объект Message, из-за чего после **успешной** отправки бросалось
  `Field "recipient" must be an object.` При отсутствии поля `message` в ответе теперь кидается
  `InvalidResponseException` с понятным сообщением.
- `ApiClient::editMessage()`: возвращает `SuccessResponse` — `PUT /messages` отвечает `{success: true}`, а не объектом
  Message. Ранее редактирование сообщения также падало на парсинге.

## Другие исправления клиента

- `EditChatBody::pin`: тип изменён с `?bool` на `?string` — `PATCH /chats` принимает ID сообщения для закрепления, а не
  булево значение.
- `AddChatMembersResult`: поле `details` переименовано в `failedUserDetails` (JSON `failed_user_details`),
  `FailedUserDetails::reason` → `error` (JSON `error`) — соответствие реальному ответу `POST /chats/{id}/members`.
  Старые имена `details`/`reason` убраны (в v1.0.4 ответ API парсится корректно).
- `AttachmentType`: добавлен тип `contact` — вложение с контактом, возвращаемое по кнопке `request_contact`. Новый DTO
  `ContactAttachmentPayload` (`hash`, `vcf_info`, `vcf_phone`, `max_info`) + маппинг в `Attachment`.
- `User::lastActivityTime` (а также `UserWithPhoto`, `ChatMember`, `BotInfo`): теперь nullable — реальный API может не
  присылать `last_activity_time` (пользователь скрыл онлайн). Ранее парсинг вебхука падал.

## Long polling

- Новый `ApiClient::getUpdatesBatch(): UpdatesResult` (`updates` + `marker`) — marker из ответа теперь сохраняется.
  `getUpdates()` сохранён без изменений сигнатуры (обёртка над batch) для обратной совместимости с
  `laravel-max-client`.
- `LongPollingRunner` переведён на `getUpdatesBatch()` и использует возвращаемый marker вместо
  `max(update.timestamp)` — более надёжная точка продолжения опроса.

## Качество

- PHP 8.4, PSR-12, PHPStan level max — 0 ошибок.
- PHPUnit зелёные (212 тестов / 508 assertions); покрытие строк 99.68% (порог 95%).
