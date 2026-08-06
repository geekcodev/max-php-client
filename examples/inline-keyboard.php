<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButton;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButtonRow;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Enum\ButtonType;

require __DIR__ . '/bootstrap.php';

/**
 * Отправка сообщения с inline-клавиатурой. Обработка нажатий —
 * через апдейт message_callback (см. examples/echo-handler.php: sendAnswer).
 */

$client = max_client();

$client->sendMessage(
    new Recipient(chatId: 123456789),
    new NewMessageBody(
        text: 'Выберите действие:',
        attachments: [
            new AttachmentRequest(
                type: AttachmentType::InlineKeyboard,
                rows: [
                    new InlineKeyboardButtonRow(buttons: [
                        new InlineKeyboardButton(type: ButtonType::Callback, text: 'Да', payload: 'yes'),
                        new InlineKeyboardButton(type: ButtonType::Callback, text: 'Нет', payload: 'no'),
                    ]),
                    new InlineKeyboardButtonRow(buttons: [
                        new InlineKeyboardButton(type: ButtonType::Link, text: 'Сайт MAX', url: 'https://max.ru'),
                    ]),
                ],
            ),
        ],
    ),
);
