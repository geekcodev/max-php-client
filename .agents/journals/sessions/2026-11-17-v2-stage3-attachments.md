---
tags: [ v2, attachments, buttons, keyboard, upload, breaking, gate ]
date: 2026-11-17
---

## Проблема

После этапа 2 оставались плоские `Attachment`/`AttachmentRequest`, `AttachmentType`/`ButtonType`, старые payload-классы
и `InlineKeyboardButton*`. Нужно привести вложения и кнопки к официальной схеме.

## Решение

- `Attachment` — abstract readonly-база (`type`) + 9 подтипов: `PhotoAttachment`, `VideoAttachment`, `AudioAttachment`,
  `FileAttachment`, `StickerAttachment`, `ContactAttachment`, `InlineKeyboardAttachment`, `ShareAttachment`,
  `LocationAttachment`.
- `AttachmentRequest` — abstract readonly-база (`type`) + 9 подтипов; payload — `PhotoAttachmentRequestPayload`,
  `UploadedInfo`, `StickerAttachmentRequestPayload`, `ContactAttachmentRequestPayload`,
  `InlineKeyboardAttachmentRequestPayload`, `ShareAttachmentPayload`.
- Payload-классы: `MediaAttachmentPayload`, `PhotoAttachmentPayload` (spec: photo_id/token/url),
  `FileAttachmentPayload`,
  `StickerAttachmentPayload`, `ShareAttachmentPayload`, `VideoThumbnail`; старые `ImageAttachmentPayload`,
  `VideoAttachmentPayload`, `AudioAttachmentPayload`, `LocationAttachmentPayload`, `InlineKeyboardAttachmentPayload`
  удалены.
- `Keyboard` (`buttons: list<list<Button>>`) и `Button` — abstract readonly-база + 7 подтипов (`CallbackButton`,
  `LinkButton`, `MessageButton`, `RequestContactButton`, `RequestGeoLocationButton`, `OpenAppButton`,
  `ClipboardButton`);
  `InlineKeyboardButton`/`InlineKeyboardButtonRow` удалены.
- `AttachmentType`, `ButtonType` удалены (решение 9 A).
- `UploadedInfo` стал token-only; добавлен `UploadEndpoint` (url + token?) — ответ `POST /uploads`;
  `Uploader::upload()` и `uploadMedia()` возвращают `UploadEndpoint`.
- `MessageBody`/`NewMessageBody` используют базовые фабрики; `VideoInfo` использует `VideoThumbnail` (переименование в
  `VideoAttachmentDetails` — этап 5).
- Обновлены тесты вложений/кнопок, `WebhookHandlerTest`, примеры (`send-media`, `inline-keyboard`, `verify-contact`),
  README и `docs/api-reference.md`.

## Тесты

`AttachmentTest` переписан: 9 типов ответа, 9 типов запроса, payload-классы, 7 кнопок, `Keyboard`, ошибки неизвестных
типов; `DtoCoverageTest` очищен от старых плоских сценариев; `WebhookHandlerTest` проверяет `ContactAttachment`.

## Нюансы

- `FileAttachment::$size` и `StickerAttachment::$width`/`$height` оставлены nullable: в смоуке top-level `size` не
  приходил, а sticker dimensions отсутствовали.
- `ContactAttachmentPayload::$hash` пока `string` с `''` (этап 5/6: `?string` и удаление `vcfPhone`).
- `VideoUrls` пока сохраняет legacy `mp4`/`preview`; удаление — этап 5.
- `Callback::$message`/`UpdateList` уже готовы, но строгость и остальные переименования — этапы 5–8.

## Gate

Lint 0/157, PHPStan level max 0, PHPUnit 372/938, покрытие 96.22% (2011/2090, методы 95.17%), audit 0. Интеграционный
смоук: 2 passed, 2 skipped — DNS timeout до `platform-api2.max.ru`; `getMe`/`getSubscriptions` прошли. Шаг 7:
README/docs § 8 обновлены для вложений/кнопок; остальные v2-разделы — этапы 4–6.
