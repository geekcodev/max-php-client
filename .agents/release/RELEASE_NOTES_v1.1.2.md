# v1.1.2 — ContactVerifier: приём хеша в hex и base64 (16.09.2026)

## Исправления

### Контакт из кнопки `request_contact` не проходил проверку, если MAX прислал хеш в base64

- `ContactVerifier::verify()` сравнивал только **нижний регистр hex**:
  `hash_equals(hash_hmac('sha256', $vcf, $token), $hash)` (64 символа). Если MAX присылал хеш в base64 (44 символа
  с «==» или 43 без паддинга), `hash_equals` всегда возвращал `false` — длины не совпадали. Бот отвечал «Не удалось
  проверить контакт», хотя контакт настоящий.
- По спеке MAX (и поведению официального клиента `maxigo-client`, `VerifyHash`) хеш принимается в **hex или base64** —
  стандартном и URL-safe, **с паддингом и без**. Теперь `verify()` декодирует все варианты и сравнивает двоичный HMAC
  константным `hash_equals` (`src/Security/ContactVerifier.php`).

### Затронутые сценарии

- `ContactVerifier::verify($vcfInfo, $hash)` — принимает хеш в hex (как раньше) и дополнительно:
  стандартный base64 с паддингом и без, URL-safe base64 с паддингом и без.
- Пустой `$vcfInfo` или `$hash` → `false` (защитный гард).
- Никаких изменений сигнатур и DTO — обратная совместимость полная.

## Обратная совместимость

- Не breaking: добавлены новые принимаемые кодировки хеша; все сценарии, ранее работавшие (hex), продолжают работать.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer) — 0 замечаний; PHPStan level max — 0 ошибок.
- PHPUnit зелёные (233 теста, 565 assertions).
- Тесты: `it_verifies_contact_hash_in_standard_base64`,
  `it_verifies_contact_hash_in_standard_base64_without_padding`,
  `it_verifies_contact_hash_in_url_safe_base64`,
  `it_verifies_contact_hash_in_url_safe_base64_without_padding`,
  `it_rejects_a_garbage_hash`, `it_rejects_an_empty_hash_or_vcf` + существующие hex/CRLF-тесты.