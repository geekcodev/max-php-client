---
tags: [ webapp, bridge, requestcontact, phone, security, hmac, hashmatcher, dry, dto, docs, gate ]
date: 2026-10-13
---

# Сессия: `WebAppContactVerifier` — контакт из моста мини-приложения

## Проблема

В мини-приложении номер можно получить не только кнопкой `request_contact` в чате, но и через мост
`window.WebApp.requestContact()`. Это другой механизм: подпись считается от `authDate`/`phone`/`userId`, а не от
`vcf_info`. Документация моста описывает формулу HMAC только словами, поэтому реальной схемы у ядра не было. У
интегратора (i2tech) рабочий верификатор жил в приложении — это security-логика не на месте и дублирование сравнения
hex/base64.

## Решение

Перенос подтверждённой продакшеном схемы в ядро:
`hash = HMAC-SHA256("authDate=<v>\nphone=<v>\nuserId=<v>", bot_token)`, `phone` — только цифры без `+`. Новые
`Security\WebAppContactVerifier` (`verify`/`resolve`, `maxAge` — replay-защита) и `Dto\WebAppContact`. Общее сравнение
хэшей вынесено в `Internal\HashMatcher`, на него переведён `ContactVerifier` (поведение не менялось). Документация:
README (Безопасность, Примеры, История), `docs/api-reference.md` раздел 9, `AGENTS.md`, пример
`examples/verify-webapp-contact.php`.

## Тесты

`HashMatcherTest` (hex, base64 standard/url-safe, мусор, пусто), `WebAppContactVerifierTest` (hex/base64, телефон с
разделителями, `resolve` с нормализованным номером, подмена userId, протухший/нулевой `authDate`, пустые входы),
`ContactVerifierTest` — регресс без изменений. Новые классы — 100% строк и методов.

## Нюансы

- Интеграторские планы: `laravel-max-client/.agents/plans/2026-10-13-webapp-contact-service-and-session.md`
  (адаптер, v1.2.2: `WebAppContactService` + `WebAppSession`) и
  `i2tech.local/.agents/plans/2026-10-13-webapp-cabinet-package-extraction.md` (вынос из приложения).
- Нормализация телефона сохранена как в проде (`preg_replace('/\D+/u', '', $phone)`), наугад не расширялась.
- Коммиты/теги — за пользователем; релиз ждёт merge `dev → main`.

## Gate

`composer run lint` — 0 из 112 файлов; PHPStan (level max) — 0; PHPUnit — 369 тестов / 941 проверка;
`composer run coverage` — 99.81% строк (1617/1620, порог 95%), новые классы 100%; `composer audit` (рецепт
`pick_ip`) — 0 уязвимостей. Реальный API не задействован (схема подтверждена продакшеном интегратора).
