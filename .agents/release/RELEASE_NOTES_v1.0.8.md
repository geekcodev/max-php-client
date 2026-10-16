# v1.0.8 — двухшаговая загрузка медиа (20.08.2026)

## Исправления

### Загрузка медиа: двухшаговый процесс

- `Uploader::upload()` теперь реализует двухшаговый процесс загрузки, как требует API MAX:
    1. `POST /uploads?type={type}` — запрос URL для загрузки (без тела запроса).
    2. `POST {url}` — отправка файла multipart на полученный URL (поле `data`).
- Токен возвращается из шага 2, если он есть; иначе — из шага 1 (fallback).
- Мultipart-поле изменено с `file` на `data` (требование внешних upload-серверов).
- `HttpClient::request()` поддерживает `absoluteUri` для запросов к внешним доменам (`fu.oneme.ru`, `iu.oneme.ru`,
  `vu.okcdn.ru`).

## Затронутые сценарии

- Загрузка изображений, видео, аудио и файлов теперь работает через два запроса вместо одного.
- Токен в `UploadResult` корректно определяется независимо от того, на каком шаге его возвращает API.

## Обратная совместимость

- Не breaking: сигнатура `upload()` не изменилась; `UploadResult` содержит те же поля `url` и `token`.
- Внутренняя реализация изменена; внешнее поведение идентично старому для потребителя.

## Качество

- PHP 8.4, PSR-12 (php-cs-fixer), PHPStan level max — 0 ошибок.
- PHPUnit зелёные; покрытие строк ≥ 95% (порог).
- Тесты: `it_performs_two_step_upload_for_file_type`, `it_uses_token_from_step2_when_provided`,
  `it_falls_back_to_token_from_step1_when_step2_has_no_token`, `it_uses_detected_mime_type_for_unknown_extension`.
