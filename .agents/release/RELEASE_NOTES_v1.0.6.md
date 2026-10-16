# v1.0.6 — sendAnswer, фикс парсинга администраторов, глобальный rate limit 30 rps, синхронизация с max-openapi (14.08.2026)

## Исправления

### sendAnswer без message

- `ApiClient::sendAnswer(string $callbackId, ?NewMessageBody $message = null)`: при `message === null` тело запроса
  теперь `{}` вместо отсутствия тела. По спецификации `/answers` requestBody **обязателен** (object), поле `message`
  опционально и заполняется только для **обновления текущего сообщения**. Раньше клиент не слал тело вообще, и API
  отвечал `400 Empty request body` → webhook-джоб падал с `ApiException`.
- Типы параметра `jsonBody` в цепочке `ApiClient::request`/`requestObject`, `Transport\HttpClient::request`,
  `Transport\RequestBuilder::request` расширены с `?array` до `array|object|null` — пустой объект передаётся как
  `(object) []` и кодируется в `{}`.

### Парсинг администраторов

- `ChatAdminPermission`: добавлены 3 deprecated-значения — `post_edit_delete_message`, `edit_message`,
  `delete_message`. Они возвращаются API для чатов со старыми правами; раньше `getBotMembership()`,
  `getChatAdmins()`, `getChatMembers()` падали с `InvalidResponseException` при их получении. Теперь парсятся корректно
  (см. `isDeprecated()`).
- `ApiClient::addChatAdmin()`: выдача deprecated-прав теперь отклоняется на клиенте (`InvalidArgumentException`)
  — спека разрешает их только в ответе API, но не для выдачи.
- `ApiClient::addChatAdmin()`: добавлен опциональный параметр `?int $marker` — кладётся в тело запроса (пагинация при
  большом количестве администраторов).
- `ApiClient::getChatAdmins()`: больше не шлёт query `marker`/`count` — в спеке их нет (параметры сохранены в сигнатуре
  для совместимости, но игнорируются; помечены deprecated).

### Документация

- `join_time` — миллисекунды (в спеке зафиксировано); глобальный лимит **30 rps** на `platform-api2.max.ru`
  (AGENTS.md, README).

### Глобальный rate limit 30 rps

- `HttpClient` применяет глобальный token bucket 30 req/s (бакет 30) к **каждому** запросу (включая ретраи и загрузку
  медиа): при исчерпании запрос ожидает пополнения, а не падает с исключением.
- `ApiClient::create()`: новая опция `global_rate_limiter` (`?RateLimiter`), по умолчанию `RateLimiter(30, 30)`.
- `RateLimiter`: добавлен метод `wait(int $chatId)` — ждёт пополнения бакета вместо бросания `RateLimitException`
  (`acquire()` сохраняет прежнее поведение); ключ `0` зарезервирован под глобальный бакет. Класс перестал быть
  `final` (метод `sleep()` переопределяем для тестов/таймингов).

## Затронутые сценарии

- Ответ на callback **без обновления сообщения** (например, кнопка обработана, но текст/клавиатура меняться не должны):
  раньше `400 Empty request body`, теперь корректный `200`, меню и кнопки остаются нетронутыми.
- Чтение `getBotMembership()`/`getChatAdmins()`/`getChatMembers()` на чатах со старыми правами: раньше падало, теперь
  работает.
- Ответ на callback с обновлением сообщения (`message: NewMessageBody`) — поведение не изменилось.

## Обратная совместимость

- Не breaking: публичные сигнатуры расширены (передача `array` по-прежнему валидна для `array|object|null`).
- `getChatAdmins(int $chatId, ?int $marker = null, ?int $count = null)` — второй и третий аргументы игнорируются
  (помечены deprecated); спека не определяет query-пагинацию для этого эндпоинта.
- Локальный per-chat rate limit (2 req/s) по-прежнему не применяется к `editMessage`/`deleteMessage`/`sendAnswer`
  (нет `chat_id`) — вместо него действует глобальный предохранитель 30 rps.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer), PHPStan level max — 0 ошибок.
- PHPUnit зелёные (215 тестов); покрытие строк 99.68% (порог 95%).
- Тест `it_sends_an_answer_without_a_message` обновлён: ожидается тело `{}` + заголовок
  `Content-Type: application/json`.
