---
tags: [ contact, hash, webhook, invalid-payload, dto, spec-sync, fix, gate ]
date: 2026-10-19
---

## Проблема

Регрессия из filament-max-chat (хост i2tech): контакт пользователя не доходит до чата вообще. Официальная OpenAPI-спека
MAX объявляет `ContactAttachmentPayload.hash` nullable, а `fromArray()` требовал строку: при `hash: null` или отсутствии
выбрасывал `InvalidResponseException`, контроллер вебхука отвечал `400 invalid_payload`, и апдейт терялся целиком.
Воспроизведено скриптом на четырёх нагрузках через `WebhookHandler::decode`: обе контактные падали. Строгое требование
вводилось в `bdfc828` (`fix!`) по тогдашним ответам, где поле всегда присутствовало.

## Решение

- `ContactAttachmentPayload::fromArray()`: `hash: Json::string($data, 'hash') ?? ''` — отсутствие/`null` дают пустую
  строку; неверный тип остаётся ошибкой разбора. Сигнатуры не менялись — patch-релиз `v1.1.11`.
- `toArray()`: пустой `hash` не сериализуется. `ContactVerifier::verify()` с пустым хэшем и раньше возвращает `false`
  (fail-closed) — `MaxContactService` в адаптере без правок, неподписанный телефон не принимается.
- Документация: `docs/api-reference.md` раздел 7, `README.md` «История изменений»,
  `.agents/release/RELEASE_NOTES_v1.1.11.md` (в работе), план `2026-10-19-contact-hash-tolerance.md`.

## Тесты

`AttachmentTest`: старый тест «отсутствующий hash — исключение» переписан на толерантность, добавлены `hash: null` и
неверный тип. `WebhookHandlerTest`: регрессионный — полный webhook с contact без `hash` декодируется, `toArray()` не
отдаёт пустой `hash`.

## Нюансы

Опросы (`type: "poll"`) и «серая область» стикера отложены решением пользователя: poll-типа нет ни в спеке, ни в
`AttachmentType` (нужна общая терпимость к неизвестным типам — отдельная задача). Стикер позже диагностирован по
прод-логу i2tech: MAX-CDN сам отдаёт stub-URL и серую заглушку — правок в ядре не требуется, подробности в журнале
filament-max-chat (сессия `2026-10-19-contact-hash-and-sticker-diagnosis.md`). Порядок выпуска: ядро `v1.1.11` первым,
затем пол `^1.1.11` и Gate в filament-max-chat; для i2tech —
`composer update geekcodev/max-php-client`.

## Gate

Прогнан целиком в dev-контейнере: lint 0/112, PHPStan 0 ошибок, PHPUnit 372 теста / 949 проверки, покрытие 99.81%
(1614/1617, порог 95%), `composer audit` 0 уязвимостей (pick_ip-рецепт раздела 8). Смоук-тесты группы `integration` не
запускались (без токена). Шаг 7 сверки документации: последний тег на origin — v1.1.10, статус AGENTS совпадает, цифры
из последнего прогона.
