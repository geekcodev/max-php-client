# v1.0.7 — sendAnswer: notification и fail-fast вместо 400 от API (15.08.2026)

## Исправления

### Ответ на callback без `message`

- `ApiClient::sendAnswer(string $callbackId, ?NewMessageBody $message = null, ?string $notification = null)`:
  добавлен опциональный параметр `$notification` — одноразовое уведомление пользователю (toast), не меняет сообщение.
- Реальный API требует `message` **или** `notification`: вызов без обоих раньше уходил с телом `{}` и падал на 400
  `Invalid request. `message` or `notification` required` → webhook-джоб получал `ApiException`. Теперь клиент отклоняет
  такой вызов сам — `InvalidArgumentException` до отправки запроса.
- Пустой `NewMessageBody` (без текста/вложений/ссылки) тоже отклоняется (`InvalidArgumentException`) — такой запрос
  бессмыслен и упирается в ту же ошибку API.
- Тело ответа собирается только из переданных полей: `{message: ...}`, `{notification: ...}` или оба сразу;
  `(object) []` для пустого тела больше не используется.

## Затронутые сценарии

- Ответ на callback **только уведомлением** (кнопка обработана, текст/клавиатура менять не нужно):
  `sendAnswer($callbackId, notification: 'Готово')` → 200, сообщение не трогается.
- Ответ на callback с обновлением сообщения (`message: NewMessageBody`) — поведение не изменилось, тело как раньше.
- Вызов `sendAnswer($callbackId)` без обоих аргументов: раньше 400 от API, теперь `InvalidArgumentException` до сети.

## Обратная совместимость

- Не breaking: публичная сигнатура расширена опциональным `$notification`; все существующие вызовы с `message` работают
  как раньше.
- Сценарий v1.0.6 «ответ без обновления сообщения» переработан: вместо тела `{}` теперь используется `notification`
  (реальный API не принимает `{}`).

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer), PHPStan level max — 0 ошибок.
- PHPUnit зелёные; покрытие строк ≥ 95% (порог).
- Тесты: `it_requires_message_or_notification_to_answer`, `it_rejects_an_empty_answer_message`,
  `it_sends_an_answer_with_a_notification`, `it_sends_an_answer_with_message_and_notification`.
