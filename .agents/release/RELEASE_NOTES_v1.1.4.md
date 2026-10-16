# v1.1.4 — LinkedMessage: sender необязателен и принимается строкой

## Исправления

### `InvalidResponseException: Field "sender" must be an integer` на `message.link`

- `LinkedMessage::fromArray()` разбирал `sender` через `Json::requiredInt()`: поле требовалось и должно было быть именно
  `int`. Фактически в объекте `link` MAX отдаёт идентификаторы **строками** — `sender: "277570130"`,
  `chat: "117541872"`, — хотя `chat` в спеке и объявлен строкой, а `sender` помечен как `integer/int64` и
  необязательный.
- Из-за этого `Message::fromArray()` падал в двух местах:
    - **ответы API**: `POST /messages` с `link` (ответ на сообщение) отправлялся, но его разбор бросал исключение —
      вызывающий терял `mid` отправленного сообщения, а повтор запроса приводил к дубликату;
    - **вебхуки**: апдейт с `message.link` (например, ответ пользователя на подтверждение) не разбирался целиком и
      отбрасывался с HTTP 400 — бот не видел сообщение пользователя.
- Теперь `sender` необязателен (`?int`), а числовая строка приводится к int. Нечисловое значение по-прежнему даёт
  `InvalidResponseException`.

## Обратная совместимость

- Не breaking для вызовов: позиционный порядок полей `LinkedMessage` сохранён, `new LinkedMessage('reply', 5, 'm')`
  работает как раньше.
- Тип свойства расширен до `?int` — код, который присваивает `$link->sender` в `int`, нужно поправить на
  `?int` (PHPStan об этом сообщит).
- `toArray()` не добавляет `sender`, если его нет. `Message`, `Update` и сигнатуры методов не менялись.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer) — 0 замечаний; PHPStan level max — 0 ошибок.
- PHPUnit зелёные (247 тестов, 594 assertions), покрытие строк 99.70% (порог 95%).
- Новые тесты: `JsonTest::it_reads_a_tolerant_int`,
  `JsonTest::it_rejects_a_non_integer_tolerant_value`,
  `DtoCoverageTest::it_reads_a_linked_message_with_a_numeric_string_sender`,
  `DtoCoverageTest::it_reads_a_linked_message_without_a_sender`,
  `DtoCoverageTest::it_reads_a_message_with_a_linked_message_sender_as_string`,
  `ApiClientEndpointsTest::it_sends_a_message_with_a_reply_link_when_the_api_returns_a_string_sender`,
  `WebhookHandlerTest::it_decodes_a_reply_update_with_a_numeric_string_link_sender`.

## Расхождение со спецификацией

- `max-openapi`: `LinkedMessage.sender` объявлен как `integer/int64`, реальный API присылает десятичную строку.
  Спецификацию стоит привести к факту (`oneOf: integer, string` или описание отклонения), иначе расхождение будет
  воспроизводиться в других клиентах.
