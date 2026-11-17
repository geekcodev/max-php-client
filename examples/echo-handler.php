<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\Update\MessageCallbackUpdate;
use GeekCo\MaxPhpClient\Dto\Update\MessageCreatedUpdate;

/**
 * Пример обработчика апдейтов: эхо-ответ на текстовые сообщения
 * и ответ на нажатие inline-кнопок.
 */
function max_echo_handle_update(ApiClient $client, Update $update): void
{
    if ($update instanceof MessageCreatedUpdate) {
        $text = $update->message->body->text;
        $senderId = $update->message->sender?->userId;

        if ($text === null || $text === '' || $senderId === null) {
            return;
        }

        $client->sendMessage(
            new Recipient(userId: $senderId),
            new NewMessageBody(text: 'Вы написали: ' . $text),
        );

        return;
    }

    if ($update instanceof MessageCallbackUpdate) {
        $client->answerCallback(
            callbackId: $update->callback->callbackId,
            message: new NewMessageBody(text: 'Кнопка нажата: ' . ($update->callback->payload ?? '')),
        );
    }
}
