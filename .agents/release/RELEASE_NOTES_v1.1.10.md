# v1.1.10 — `WebAppContactVerifier`: номер телефона из моста мини-приложения

Статус: **выпущено** 2026-10-16. Тег `v1.1.10` на merge-коммите `dev → main`. Хэш merge-коммита и подтверждение
публикации Packagist дописываются сразу после выхода: файл лежит в `.agents/`, а каталог помечен `export-ignore`,
поэтому эта правка не попадает в дистрибутив и не расходится с опубликованной версией.

## Новое: проверка контакта, затребованного мини-приложением

В мини-приложении номер телефона можно получить не только кнопкой `request_contact` в чате, но и через мост
`window.WebApp.requestContact()`. Это **другой** механизм: подпись считается не от `vcf_info`, а от трёх полей payload'а
в алфавитном порядке ключей, склеенных через `\n`, а телефон идёт без `+`:

```
hash = HMAC-SHA256("authDate=<v>\nphone=<v>\nuserId=<v>", bot_token)
```

- Новый `Security\WebAppContactVerifier` (`verify(string $authDate, string $phone, string $userId, string $hash)` и
  `resolve(...): ?Dto\WebAppContact`). Телефон нормализуется в цифры без `+` (`preg_replace('/\D+/u', '', $phone)`), хэш
  принимается в hex или base64 (стандартной и URL-safe, с паддингом и без), сравнение — константное. При
  `maxAge > 0` отбрасывается устаревший `authDate` (replay-защита). Номер и payload не логируются (OWASP A09).
- Новый `Dto\WebAppContact` — подтверждённые значения (`userId`, `authDate`, `phone`).
- Схема подтверждена продакшеном интегратора (i2tech): мини-приложение `/webapp` с авторизацией по контакту работает,
  заявки пользователей отдаются в кабинете. Расхождение с документацией моста (формула HMAC описана только словами)
  записано в `docs/api-reference.md`, раздел 9.
- Документировано ограничение MAX: данные `requestContact()` используются только для взаимодействия с текущим
  мини-приложением (`README.md`, раздел «Безопасность»; `docs/api-reference.md`, раздел 7).

## Внутреннее: устранение дублирования

- Новый `Internal\HashMatcher::matches()` — общее сравнение бинарного HMAC с значением в hex/base64. На него переведён
  `ContactVerifier` (приватные `matchesHex`/`base64Variants` удалены). Публичное поведение и сигнатуры не менялись.

## Репозиторий

- Каталог `.agents/` (планы, журнал сессий, release notes) перенесён из `.gitignore` в git — история сессий доступна при
  `git clone`, как в `filament-max-users` и `laravel-max-client`; в `.gitattributes` добавлены
  `/.agents export-ignore` и `/.agents/** export-ignore`, поэтому в дистрибутив Packagist каталог не входит. Правки
  `AGENTS.md` (разделы 2, 4, 5, шаг 8 Gate, чек-лист) под это состояние.

## Состав

Код: `src/Internal/HashMatcher.php`, `src/Security/WebAppContactVerifier.php`, `src/Dto/WebAppContact.php`,
`src/Security/ContactVerifier.php` (рефакторинг на `HashMatcher`). Тесты: `HashMatcherTest`,
`WebAppContactVerifierTest`, регресс `ContactVerifierTest` без изменений. Документация: `README.md` (разделы
«Безопасность», «Примеры», «История изменений»), `docs/api-reference.md` (разделы 7 и 9), `AGENTS.md`, пример
`examples/verify-webapp-contact.php`. Репозиторий: `.gitignore`, `.gitattributes` и каталог `.agents/` (планы, журнал,
release notes — впервые в git, с `export-ignore`).

## Качество

Gate: lint 0 из 112 файлов, PHPStan (level max) 0, PHPUnit 369 тестов / 941 проверка, покрытие 99.81% строк (порог 95%),
новые классы 100%; `composer audit` (рецепт `pick_ip`) — 0 уязвимостей. После прогона Gate менялись только md-файлы,
`.gitignore` и `.gitattributes` — PHP-код идентичен прогнанному.
