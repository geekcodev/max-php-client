---
tags: [ v2, markup, typed-dto, breaking, gate ]
date: 2026-11-17
---

## Проблема

Плоский `MarkupElement` с nullable `url`/`userId`/`userLink` не отражал 10 типов разметки и позволял невозможные
сочетания полей.

## Решение

- `MarkupElement` — abstract readonly-база (`type: Markup`, `from`, `length`) с фабрикой по `type`.
- 10 подтипов в `src/Dto/Markup/`: `StrongMarkup`, `EmphasizedMarkup`, `UnderlineMarkup`, `StrikethroughMarkup`,
  `MonospacedMarkup`, `HighlightedMarkup`, `QuoteMarkup`, `HeadingMarkup`, `LinkMarkup` (`url`), `UserMentionMarkup`
  (`user_id?`, `user_link?`).
- Алиас `underlined` из `discriminator.mapping` принимается и нормализуется в `underline`; `toArray()` всегда отдаёт
  `underline`.
- `MessageBody`/`CommentMessageBody` используют `list<MarkupElement>`; обновлены `MarkupElementTest`, `CommentTest`,
  README и `docs/api-reference.md` § 8.

## Тесты

`MarkupElementTest` переписан: все 10 типов, link/user_mention, `underlined`, ошибки, разметка сообщения, round-trip;
`CommentTest` проверяет `LinkMarkup`.

## Нюансы

- `Markup` enum сохранён (10 значений) — это discriminator-значения, не отдельная схема.
- `from`/`length` остаются обязательными int.
- `userId` читается `tolerantInt` для числовых строк.

## Gate

Lint 0/167, PHPStan level max 0, PHPUnit 369/910, покрытие 96.33% (2076/2155, методы 95.40%), audit 0. Интеграционный
смоук в этапе 4 не перезапускался (разметка не входит в read-only probe); этап 3: 2 passed, 2 skipped по DNS.
