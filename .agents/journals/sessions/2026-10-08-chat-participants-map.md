---
tags: [ chat, participants, dto, getchat, invalid-response, fix, gate ]
date: 2026-10-08
---

# Сессия: `Chat::$participants` — карта участников вместо списка

## Проблема

Кнопка «Обновить из MAX» в `filament-max-users` падала: `getChat()` группы возвращает `participants` объектом
`{user_id: <активность, мс>}`, а `Chat::fromArray()` разбирал его как `list<User>` и кидал
`InvalidResponseException: Field "user_id" must be an integer`. Диалоги и `getChatMembers` работали.

## Решение

`Json::intMap()` — карта «id => int» с отклонением нечисловых ключей/значений; `Chat::$participants` —
`array<int, int>` (`user_id => активность, мс`), `null` для диалогов и списков чатов; конструктор не менялся, форма
`list<User>` в проде недостижима. `docs/api-reference.md`: разделы 8 и 9.

## Тесты

`JsonTest` +3 (карта, нечисловый ключ, нечисловое значение), `DtoTest` +2 (карта, отклонение списка),
`DtoCoverageTest` — фикстура заменена картой.

## Нюансы

Прямых чтений поля в `laravel-max-client`/filament нет. v1.1.9 занят этим фиксом; план проверки телефона перенумерован
на v1.1.10. Долг v1.1.8 вышел в релизе частично: пункт README в тег попал, а статус `AGENTS.md`
не был обновлён до установки тега и вошёл в опубликованную версию со значением v1.1.8 — исправление лежит в `dev`
отдельным коммитом. Релиз: тег `v1.1.9` = `a701474` (PR #22), GitHub Release, Packagist — та же ссылка (проверено по
p2-метаданным Packagist и странице релиза).

## Gate

`composer run lint` — 0 из 107 файлов; `vendor/bin/phpstan analyse` — 0 ошибок; `vendor/bin/phpunit` — 356 тестов / 915
проверок; `composer run coverage` — 99.81% строк (1582/1585, порог 95%); `composer audit` (рецепт `pick_ip`) — 0
уязвимостей. Реальный API (`--network host`, CA Минцифры): `getChat(-79032376695376)` — карта из 10 участников,
`getChat(117541872)` — `participants: null`.
