# v1.0.2 — Исправление возвращаемого типа `createSubscription`

## Исправления

- `ApiClient::createSubscription()` теперь возвращает `SuccessResponse` (`{success, message?}`) вместо
  `Subscription` — приведено в соответствие с фактическим ответом API `POST /subscriptions`.

## Внимание адаптерам

Изменение ломающее: вместо полей `Subscription` (`url`, `update_types`) проверяйте `success` из
`SuccessResponse`. Адаптеры (`geekcodev/laravel-max-client`) должны обновиться на `^1.0.2`.

## Качество

- PHP 8.4, PSR-12, PHPStan level max — 0 ошибок.
- PHPUnit зелёные; покрытие строк ≥95%.
