<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\Button\CallbackButton;
use GeekCo\MaxPhpClient\Dto\Button\LinkButton;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\InlineKeyboardAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;

require __DIR__ . '/bootstrap.php';

/**
 * Отправка сообщения с inline-клавиатурой. Обработка нажатий —
 * через апдейт message_callback (см. examples/echo-handler.php: answerCallback).
 */

$client = max_client();

$client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(
        text: 'Выберите действие:',
        attachments: [
            new InlineKeyboardAttachmentRequest(
                new InlineKeyboardAttachmentRequestPayload([
                    [
                        new CallbackButton('Да', 'yes'),
                        new CallbackButton('Нет', 'no'),
                    ],
                    [
                        new LinkButton('Сайт MAX', 'https://max.ru'),
                    ],
                ]),
            ),
        ],
    ),
);
