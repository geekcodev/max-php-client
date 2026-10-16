# v1.0.5 — open_app кнопка: поле web_app вместо app_data

## Исправление

- `InlineKeyboardButton`: для типа `open_app` поле `appData` (JSON `app_data`) заменено на `webApp` (JSON `web_app`) —
  публичное имя (username) бота или ссылка на него, чьё мини-приложение нужно открыть. Добавлен опциональный
  `contactId` (JSON `contact_id`) — ID бота-альтернатива.
- Причина: реальный API отклонял кнопку с `app_data` ответом `400 Field 'webApp' cannot be null`. Правильный формат —
  `{type: 'open_app', text: ..., web_app: '<username_бота>'}` (+ опционально `payload` — стартовый параметр
  мини-приложения). URL самого мини-приложения задаётся на стороне платформы MAX (профиль → Чат-боты → Расширенные
  настройки), а не в кнопке.
- Сигнатура конструктора `InlineKeyboardButton` изменена (`appData` → `webApp`/`contactId`) — breaking change.

## Затронутые сценарии

- Отправка меню с кнопкой «Открыть приложение» (`sendMessage`), ответы на callback с обновлённой клавиатурой
  (`POST /answers`).

## Качество

- PHP 8.4, PSR-12, PHPStan level max — 0 ошибок.
- PHPUnit зелёные; покрытие строк ≥ 95% (порог).
