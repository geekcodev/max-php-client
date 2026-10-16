# План: фикс `Chat::$participants` и выпуск цепочки пакетов

Статус: в работе — ядро и `filament-max-users` готовы, остались шаги 6 и 8 (vendor и журнал в
`laravel-max-client`). Целевая версия ядра: **v1.1.9** (patch), выпущена 2026-10-08. Контракт конструктора `Chat` не
менялся, менялся только тип элементов `participants` (`list<User>` → `array<int, int>`): старая форма была недостижима в
проде (парсинг групп падал), поэтому фикс не считается ломающим. Релиз v1.1.9 занимает этот фикс; план проверки телефона
из MAX Bridge перенумерован на v1.1.10.

## Цель

Кнопка «Обновить из MAX» на странице чата в `filament-max-users` (и любое другое обращение к `getChat()`)
должна успешно обновлять метаданные групп и каналов, а не падать с
`InvalidResponseException: Field "user_id" must be an integer`.

## Исследование

- Воспроизведено на реальном API: `GET /chats/{id}` группы (HTTP 200) отдаёт `participants` объектом
  `{ "<user_id>": <время активности, мс> }`, диалоги — `participants: null`; спека `max-openapi` (схема `Chat`)
  тоже объявляет `type: [object, "null"]`, «Участники с временем активности».
- Ошибка парсинга возникала в `Chat::fromArray()` → `Json::map()` ожидал список объектов `User`.
- Прямых чтений `Chat::$participants` в `laravel-max-client` и filament-плагинах нет.

## Шаги

1. [x] Воспроизведение и диагностика (кнопка чата в `filament-max-users`, реальный ответ `getChat` группы).
2. [x] Фикс в ядре: `Json::intMap()`, `Chat::$participants` как `array<int, int>`, тесты (JsonTest, DtoTest,
   DtoCoverageTest), `docs/api-reference.md` (разделы 8 и 9).
3. [x] Gate ядра: lint 0/107, PHPStan 0, PHPUnit 356/915, покрытие 99.81%, `composer audit` 0.
4. [x] Проверка реальным API из локального `src/`: группа — карта из 10 участников, диалог — `participants: null`.
5. [x] Релиз v1.1.9: release notes (статус «выпущено») → merge PR `dev → main` (`a701474`) → тег `v1.1.9` → GitHub
   Release → Packagist (ссылка `a701474`, проверено по p2-метаданным). Состав: `a79da0f` + merge; в тег вошли долг
   v1.1.8 в README и `docs/api-reference.md`, но `AGENTS.md` остался со статусом v1.1.8 — исправлено в `dev` после
   релиза.
6. [ ] `laravel-max-client`: ограничение `^1.1.8` уже допускает 1.1.9 — **не сделано**: `composer.lock` всё ещё на
   v1.1.8 (`9b3ee06`), vendor не обновлён, в release notes упоминания фикса participants нет.
7. [x] `filament-max-users`: vendor обновлён (v1.1.8 → v1.1.9, `a701474` в `composer.lock`), добавлен feature-тест
   `test_refresh_metadata_action_parses_group_participants_map`, Gate прогнан, запись внесена в
   `RELEASE_NOTES_v1.1.1.md` (сам тег плагина `v1.1.1` ещё не поставлен — его цикл выпуска).
8. [ ] Обновить журнал и статусы планов во всех трёх репозиториях — **частично**: журналы `max-php-client` и
   `filament-max-users` обновлены, в `laravel-max-client` записи за 2026-10-08 нет (закрывается вместе с шагом 6).

## Нюансы

- Сеть до Packagist из окружения нестабильна: `composer audit` и `composer update` — по рецепту `pick_ip`
  из `AGENTS.md`.
- Промежуточная проверка без публикации возможна через локальный `src/` (автолоад пакета указывает на `src/`), но
  витрина filament-плагина фикс увидит только после обновления vendor.
