<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Enum\UpdateType;

/**
 * Пример обработчика апдейтов: эхо-ответ на текстовые сообщения
 * и ответ на нажатие inline-кнопок.
 */
function max_echo_handle_update(ApiClient $client, Update $update): void
{
    if ($update->updateType === UpdateType::MessageCreated) {
        $text = $update->message?->body?->text;
        if ($text === null || $text === '') {
            return;
        }

        $client->sendMessage(
            new Recipient(userId: $update->user->userId),
            new NewMessageBody(text: 'Вы написали: ' . $text),
        );

        return;
    }

    if ($update->updateType === UpdateType::MessageCallback) {
        $callback = $update->callback;
        if ($callback === null) {
            return;
        }

        $client->sendAnswer(
            callbackId: $callback->callbackId,
            message: new NewMessageBody(text: 'Кнопка нажата: ' . ($callback->payload ?? '')),
        );
    }
}
