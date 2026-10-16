# v1.1.0 — тип dialog_with_user: UserWithPhoto (02.09.2026)

## Исправления

### Аватар собеседника личного диалога не парсился

- Эндпоинт `GET /chats/{chatId}` возвращает собеседника в поле `dialog_with_user`, которое по OpenAPI-спеке
  (`max-openapi`) типизировано как `UserWithPhoto` (несёт `avatar_url`,
  `full_avatar_url`, `description`).
- В клиенте поле было ошибочно типизировано как `User` — упрощённый объект без полей аватара. Метод `User::fromArray()`
  молча отбрасывал `avatar_url`/`full_avatar_url` при парсинге, и аватар собеседника терялся.
- Теперь `Chat::$dialogWithUser` имеет тип `?UserWithPhoto` и корректно парсит все поля (`src/Dto/Chat.php`).

### Затронутые сценарии

- `Chat::fromArray()` / `$chat->dialogWithUser` — объект теперь `UserWithPhoto` с доступными
  `avatarUrl`, `fullAvatarUrl`, `description`.
- Приложения, читавшие `$chat->dialogWithUser` как `User`, продолжат работать без изменений
  (`UserWithPhoto extends User` — обратная совместимость сохранена).

## Обратная совместимость

- Не breaking: `UserWithPhoto` наследует `User`, поэтому все существующие сигнатуры и проверки
  `instanceof User` остаются валидными.
- Поле возвращает больше данных (аватар, описание), ранее отбрасывавшихся — расширение возможностей, не нарушение
  контракта.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer), PHPStan level max — 0 ошибок.
- PHPUnit зелёные (225 тестов, 556 assertions); покрытие строк 99.69% (порог 95%).
- Тесты: `it_parses_dialog_with_user_as_user_with_photo` (roundtrip с `avatar_url`/
  `full_avatar_url`/`description`).
