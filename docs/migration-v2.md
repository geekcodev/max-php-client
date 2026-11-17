# Миграция на v2.0.0

Версия **2.0.0** приводит клиент к официальной схеме MAX (`max-openapi@a3ae6e7`, `api-schema` v0.0.34). Пакет ломает
публичный API: переименованы DTO и методы, плоские модели заменены типизированными иерархиями, удалены
`@deprecated`-поля.

## 1. Типизированные события

Плоский `Update` заменён abstract-базой (`updateType: string`, `timestamp`) и 19 подтипами.

Было:

```php
if ($update->updateType === UpdateType::MessageCreated) {
    $text = $update->message?->body?->text;
    $userId = $update->user?->userId;
}
```

Стало:

```php
use GeekCo\MaxPhpClient\Dto\Update\MessageCreatedUpdate;

if ($update instanceof MessageCreatedUpdate) {
    $text = $update->message->body->text;
    $userId = $update->message->sender?->userId;
    $chatId = $update->message->recipient->chatId;
}
```

- `UpdateType` удалён; используйте `instanceof` или `$update->updateType === 'message_created'`.
- `Update::$comment` удалён: `comment_created`/`comment_edited` — это `CommentCreatedUpdate::$message` /
  `CommentEditedUpdate::$message` (`CommentMessage`).
- `Update::$user`/`$chatId`/`$isChannel`/`$title`/… больше не общие: они находятся в подтипах.
- `MessageCallbackUpdate` содержит `callback` и `message?`; сообщение читается с верхнего уровня с фолбэком
  `callback.message`.

## 2. Вложения

`Attachment`/`AttachmentRequest` — abstract-базы + по 9 подтипов.

Было:

```php
$attachment = new Attachment(AttachmentType::Image, new ImageAttachmentPayload(url: $url));
if ($attachment->type === AttachmentType::Image) { ... }
```

Стало:

```php
use GeekCo\MaxPhpClient\Dto\Attachment\PhotoAttachment;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentPayload;

$attachment = new PhotoAttachment(new PhotoAttachmentPayload(photoId: 1, token: $token, url: $url));
if ($attachment instanceof PhotoAttachment) { ... }
```

| Было                                 | Стало                                               |
|--------------------------------------|-----------------------------------------------------|
| `ImageAttachmentPayload`             | `PhotoAttachmentPayload`                            |
| `PhotoAttachmentPayload` (thumbnail) | `VideoThumbnail`                                    |
| `VideoAttachmentPayload`             | `MediaAttachmentPayload`                            |
| `AudioAttachmentPayload`             | `MediaAttachmentPayload`                            |
| `InlineKeyboardAttachmentPayload`    | `Keyboard`                                          |
| `LocationAttachmentPayload`          | `LocationAttachment` (координаты на верхнем уровне) |
| `AttachmentType`                     | `instanceof` подтипа                                |

Подтипы ответа: `PhotoAttachment`, `VideoAttachment`, `AudioAttachment`, `FileAttachment`, `StickerAttachment`,
`ContactAttachment`, `InlineKeyboardAttachment`, `ShareAttachment`, `LocationAttachment`. Подтипы запроса:
`PhotoAttachmentRequest`, `VideoAttachmentRequest`, `AudioAttachmentRequest`, `FileAttachmentRequest`,
`StickerAttachmentRequest`, `ContactAttachmentRequest`, `InlineKeyboardAttachmentRequest`, `ShareAttachmentRequest`,
`LocationAttachmentRequest`.

`FileAttachment::$filename`/`$size` читаются с верхнего уровня с фолбэком в `payload`; `StickerAttachment`
`width`/`height` nullable.

## 3. Кнопки и клавиатура

`InlineKeyboardButton`/`InlineKeyboardButtonRow`/`ButtonType` удалены.

Было:

```php
new AttachmentRequest(AttachmentType::InlineKeyboard, rows: [
    new InlineKeyboardButtonRow([
        new InlineKeyboardButton(ButtonType::Callback, 'Да', payload: 'yes'),
    ]),
]);
```

Стало:

```php
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\InlineKeyboardAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\Button\CallbackButton;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardAttachmentRequestPayload;

new InlineKeyboardAttachmentRequest(new InlineKeyboardAttachmentRequestPayload([
    [new CallbackButton('Да', 'yes')],
]));
```

Подтипы: `CallbackButton`, `LinkButton`, `MessageButton`, `RequestContactButton`, `RequestGeoLocationButton`,
`OpenAppButton`, `ClipboardButton`.

## 4. Разметка

`MarkupElement` — abstract-база + 10 подтипов: `StrongMarkup`, `EmphasizedMarkup`, `UnderlineMarkup`,
`StrikethroughMarkup`, `MonospacedMarkup`, `HighlightedMarkup`, `QuoteMarkup`, `HeadingMarkup`, `LinkMarkup` (`url`),
`UserMentionMarkup` (`userId?`, `userLink?`).

```php
foreach ($message->body?->markup ?? [] as $element) {
    if ($element instanceof LinkMarkup) {
        $element->url;
    }
}
```

`Markup` enum сохранён; `underlined` из спеки нормализуется в `underline`.

## 5. Переименования DTO

| v1                     | v2                             |
|------------------------|--------------------------------|
| `SuccessResponse`      | `SimpleQueryResult` (+ `code`) |
| `ErrorResponse`        | `Error`                        |
| `VideoInfo`            | `VideoAttachmentDetails`       |
| `EditChatBody`         | `ChatPatch` (+ `description`)  |
| `BotCommandsResult`    | `BotCommandsInfo`              |
| `ChatMembersResult`    | `ChatMembersList`              |
| `AddChatMembersResult` | `ModifyMembersResult`          |
| `ChatListResult`       | `ChatList`                     |
| `Update` (плоский)     | `Update` (база) + 19 подтипов  |
| `UpdatesResult`        | `UpdateList`                   |

## 6. Методы

| v1                           | v2                            |
|------------------------------|-------------------------------|
| `sendBotAction()`            | `sendChatAction()`            |
| `sendAnswer()`               | `answerCallback()`            |
| `getComment()`               | `getCommentById()`            |
| `getVideoInfo()`             | `getVideoAttachmentDetails()` |
| `getUpdates(): list<Update>` | `getUpdates(): UpdateList`    |
| `getUpdatesBatch()`          | удалён                        |
| `getChats()`                 | удалён                        |

`uploadMedia()` теперь возвращает `UploadEndpoint` (`url`, `token?`); `UploadedInfo` — только `token` для payload
video/audio/file. `UploadResult` удалён.

`addChatAdmin()` больше не принимает `marker`.

## 7. Строгость и контакты

- `Message.body`, `Callback.user`, `LinkedMessage.message`, `Subscription.time` пока остаются nullable/tolerant:
  строгость включается только по подтверждённым смоуком полям.
- `ContactAttachmentPayload::$hash` — `?string`; `vcfPhone` удалён из входящего DTO (телефон — из подписанного
  `vcf_info` через `ContactPhoneExtractor::fromVcf()`).
- `ContactVerifier::verify(string $vcfInfo, ?string $hash): bool` — fail-closed на `null`/`''`.
- `User.name` сохранён как обычное nullable-поле (никнейм/отображаемое имя), несмотря на отсутствие в официальной схеме.
- `Recipient::$chatType` — `?ChatType`; `LinkedMessage` использует `MessageLinkType`.

## 7.1. Новые проверки на стороне клиента

- `NewMessageBody` и `NewCommentBody` отклоняют текст длиннее 4000 символов (`InvalidArgumentException`) — раньше
  запрос уходил в API и падал с ошибкой сервера.
- `answerCallback()` отклоняет пустой `callback_id`.
- `LongPollingRunner` выдерживает минимум 300 мс между `getUpdates` — циклы, рассчитанные на «мгновенный» опрос,
  станут медленнее, зато не упираются в ограничение API.

## 8. Удалённые поля

- `Callback::$message` — используйте `MessageCallbackUpdate::$message`.
- `MessageBody::$caption`, `MessageBody::$format` — используйте `markup`.
- `NewMessageLink::$chat` — чат задаётся получателем.
- `VideoUrls::$mp4`/`$preview` — используйте `mp4_144`…`mp4_1080`/`hls`.
- `FileAttachmentPayload::$file_size`/`$filename`/`$size` — метаданные на `FileAttachment`.
- `Attachment::$filename`/`$latitude`/`$longitude` — в подтипах.

## 9. Обёртки ответов

Методы возвращают DTO ответа, а не развёрнутые значения:

```php
$result = $client->sendMessage($recipient, $body);
$message = $result->message;

$updates = $client->getUpdates();
foreach ($updates->updates as $update) { ... }
```

`getChatAdmins()` — исключение: возвращает `ChatAdminsResult {members: ChatMember[]}`, потому что endpoint отдаёт
`members`, а схема `ChatAdminsList {admins}` не используется.

## 10. Порядок перехода

1. Обновите constraint до `^2.0`.
2. Замените `UpdateType` на `instanceof` подтипов.
3. Замените `AttachmentType`/`ButtonType` на подтипы.
4. Обновите `sendAnswer`/`getComment`/`getVideoInfo`/`sendBotAction`.
5. Уберите обращения к удалённым полям (`caption`, `format`, `chat`, `Callback::$message`, `vcfPhone`).
6. Обновите возвраты методов (`->updates`, `->message`, `->messages`).
7. Прогоните тесты; при использовании контактов проверьте fail-closed на `hash: null`.
