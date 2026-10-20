# Имя файлового вложения в DTO (вместе с релизом v1.1.11)

Дата: 2026-10-19. Статус: код и Gate готовы, ждёт merge `dev → main` и публикации `v1.1.11` по явному запросу.

## Задача

В админке i2tech файл из мессенджера отображался под служебным именем `getfile` (последний сегмент URL скачивания).
Настоящее имя файла есть только в вебхуке MAX, но DTO его не читал.

## Шаги

1. Изучить источники: официальная `schema.yaml` — `filename`/`size` на верхнем уровне `FileAttachment`; спека
   max-openapi — в `payload`; `Attachment::fromArray()` не читает ни одно место. ✓
2. `Attachment::$filename`: необязательный параметр конструктора в конечной позиции, разбор верхнего уровня с fallback в
   `payload`, `toArray()` — на верхнем уровне. ✓
3. Тесты `AttachmentTest`: обе формы, приоритет верхнего уровня, неверный тип, отсутствие имени. ✓
4. Документация: `docs/api-reference.md` раздел 8, `README.md` (история), release notes v1.1.11, журнал. ✓
5. Gate: lint, PHPStan, PHPUnit + покрытие, `composer audit`. ✓
6. Релиз `v1.1.11` (merge, тег, GitHub Release, Packagist) — по запросу пользователя. ✗

## После релиза (проект i2tech)

Этапы 2 и 3 этого плана уже сделаны под будущую версию; сводный порядок и ограничение по `vendor` — в плане приложения
`i2tech.local/.agents/plans/2026-10-19-attachment-filename.md`.

1. `composer update geekcodev/max-php-client`.
2. `AttachmentService::downloadFromMax()` подставляет `$attachment->filename` вместо имени из URL. ✓
3. Отображение старых строк: вместо служебного `getfile` — аккуратный фолбэк по mime-типу. ✓
