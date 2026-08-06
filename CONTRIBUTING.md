# Contributing

Спасибо за интерес к `geekcodev/max-php-client`. Перед началом работы прочитай
[`AGENTS.md`](./AGENTS.md) — это источник правил проекта (архитектура, команды, обязательные проверки), и [
`README.md`](./README.md) — пользовательская документация.

## Начало работы

PHP и Composer на хосте не обязательны — вся разработка идёт в Docker (PHP 8.4):

```bash
docker compose run --rm app composer install
```

## Что можно менять

- Ядро (`src/`) — клиент, DTO, enums, исключения, transport/retry/rate-limit/upload/webhook.
- Тесты (`tests/`) — unit-тесты обязательны для нового кода; HTTP-слой — через `tests/Support/MockHttpClient`.
- Примеры (`examples/`) — только с `bootstrap.php` и без токенов в коде.
- Документацию (`README.md`, `AGENTS.md`) — она обязана оставаться синхронной с кодом и API.

## Правила кода

- PHP 8.4, `declare(strict_types=1)`, PSR-12, PHPStan level max.
- SOLID / DRY / KISS; без избыточной абстракции и дублирования.
- Не добавлять комментарии без необходимости.
- Не выдумывать сигнатуры и эндпоинты — сверяйся с `AGENTS.md` (раздел «Источник истины») или
  спецификацией https://github.com/geekcodev/max-openapi.
- Секреты и токены никогда не попадают в код, логи и коммиты.

## Обязательный Gate перед завершением задачи

После любых изменений в `src/`, `tests/`, `examples/` прогони все шаги (команды — в `AGENTS.md`, раздел 7):

1. `composer run lint` — 0 файлов с правками.
2. При наличии правок — `composer run format`, затем lint снова.
3. `vendor/bin/phpstan analyse` — 0 ошибок.
4. `vendor/bin/phpunit` — все зелёные.
5. `composer run coverage` — покрытие строк ≥ 95%.

Интеграционные смоук-тесты (read-only, реальный API) запускаются отдельно с `MAX_API_TOKEN`
(команда и нюансы — в `AGENTS.md`). Без токена они пропускаются, а не падают.

## Процесс изменений

1. Ветвление от `dev` (`git checkout -b fix/...` или `feature/...`).
2. Внести изменения + тесты, прогнать Gate.
3. Открыть PR `dev` → `main` для релиза или PR в `dev` для промежуточных изменений.

## Релиз

Релиз делает владелец пакета: merge PR `dev → main` → `git tag vX.Y.Z` → GitHub Release → Packagist (автообновление по
webhook). `version` в `composer.json` не указывается — версия берётся из тегов. Подробности — в `AGENTS.md`, раздел 8.
