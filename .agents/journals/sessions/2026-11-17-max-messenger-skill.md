---
tags: [ skill, max-messenger, limits, long-polling, callback, text-limit, gate ]
date: 2026-11-17
---

## Проблема

У MAX появился официальный набор навыков для агентов (`max-messenger/max-agents-skill`): нужно было понять, даёт ли он
жёсткие ограничения API, которых нет в справочнике, и внедрить безопасную часть до v2.0.0.

## Решение

- Навык установлен копией в `.agents/skills/max-messenger/` (коммит `22b2eb9`, Apache-2.0); `SOURCE.md` фиксирует
  источник, `.agents` помечен `export-ignore`.
- Рабочая копия для OpenCode — `~/.config/opencode/skills/max-messenger/`, ID `max-messenger` (виден в `/api/skill`);
  правила загрузки — `AGENTS.md`, разделы 3 (правило 11) и 5.1.
- Аудит по `references/` — `.agents/plans/2026-11-17-max-messenger-skill-audit.md`.
- Внедрено: интервал long polling ≥300 мс, fail-fast на пустой `callback_id`, лимит 4000 символов в `NewMessageBody` и
  `NewCommentBody`. На каждую — тест.

## Тесты

PHPUnit 372/907, зелёные; покрытие 96.29% строк (2075/2155), методы 95.38% (434/455).

## Нюансы

- Схема `api-schema` v0.0.34 — источник истины: расхождения с навыком (например, «retired»
  `POST /chats/{chatId}/members`) в код не переносились.
- UX-валидации (лимиты клавиатуры, взаимоисключение `url`/`token`/`photos`, минимум один ряд) отложены — чек-лист в
  аудите.
- Строгость `required`-полей ждёт расширенных payload-выборок (решение 7 B).

## Gate

Lint 0/167, PHPStan 0, PHPUnit 372/907, покрытие 96.29% (2075/2155), `composer audit` 0.
