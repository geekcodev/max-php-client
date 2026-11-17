<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\PhotoAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Enum\UploadType;

require __DIR__ . '/bootstrap.php';

/**
 * Загрузка и отправка медиа. uploadMedia выполняет всю цепочку:
 * запрос upload-URL, бинарная загрузка, ожидание готовности вложения.
 * Положите файл photo.jpg рядом с этим примером (или укажите свой путь).
 */

$client = max_client();

$file = __DIR__ . '/photo.jpg';
if (!is_file($file)) {
    fwrite(STDERR, sprintf("File not found: %s\n", $file));
    exit(1);
}

$upload = $client->uploadMedia(UploadType::Image, $file);

$client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(
        text: '*Подпись к фото*',
        attachments: [
            new PhotoAttachmentRequest(new PhotoAttachmentRequestPayload(token: $upload->token)),
        ],
        format: TextFormat::Markdown,
    ),
    disableLinkPreview: true,
);
