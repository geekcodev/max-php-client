# v1.0.3 — Синхронизация с max-openapi + фикс парсинга Update

## Исправления

- `Update::fromArray()`: `user`/`chat_id` фолбэки — для `message_created`/`message_edited`/`message_callback` поля
  берутся из `message.sender`/`message.recipient` и `callback.message`, если отсутствуют на верхнем уровне. Реальный API
  их на верхнем уровне не присылает — раньше такие апдейты падали.
- `Update::$user` теперь nullable (`?User`): `message_removed` и `message_callback` с удалённым сообщением приходят без
  объекта `user`.
- Добавлены новые поля `Update`: `user_locale`, `title`, `payload`, `muted_until`, `message_id`, `user_id`,
  `inviter_id`, `admin_id` (заполняются для соответствующих типов событий).

## Ломающие изменения (синхронизация с max-openapi)

| Метод/DTO              | Было                                    | Стало                                              |
|------------------------|-----------------------------------------|----------------------------------------------------|
| `getPinnedMessage()`   | читает поле `pin`                       | читает поле `message` (`{message: Message\|null}`) |
| `sendBotAction()`      | тело `{"type": ...}`                    | тело `{"action": ...}`                             |
| `addChatAdmin()`       | тело `{user_id, permissions, alias}`    | тело `{"admins": [{user_id, permissions, alias}]}` |
| `getChatAdmins()`      | `ChatAdminsResult::$admins` (ChatAdmin) | `ChatAdminsResult::$members` (ChatMember)          |
| `editBotCommands()`    | возврат `SuccessResponse`               | возврат `BotCommandsResult` (`{commands\|null}`)   |
| `VideoInfo`            | `video_token, file_name, size, url`     | `token, urls, thumbnail, width, height, duration`  |
| `NewMessageLink`       | `type, url, token`                      | `type, mid, chat`                                  |
| `Chat::$icon`          | `?string`                               | `?Image` (`{url}`)                                 |
| `EditChatBody::$icon`  | `?string`                               | `?ChatIcon` (`{url, payload}`)                     |
| `Recipient::$chatType` | —                                       | новое поле `chat_type`                             |

## Внимание адаптерам

Изменения ломающие. Адаптеры (`geekcodev/laravel-max-client`) должны обновиться на `^1.0.3`: проверить чтение
`ChatAdminsResult->members` (тип `ChatMember` вместо `ChatAdmin`), возврат `editBotCommands()`, ответ
`getPinnedMessage()`, поля `VideoInfo`/`NewMessageLink`.

## Качество

- PHP 8.4, PSR-12, PHPStan level max — 0 ошибок.
- PHPUnit зелёные; покрытие строк ≥95%.
