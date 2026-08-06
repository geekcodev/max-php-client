<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;

require __DIR__ . '/bootstrap.php';

/**
 * Отправка личного сообщения пользователю и получение информации о нём.
 *
 * Где взять user_id и chat_id:
 *  - любой апдейт (bot_started, message_created, message_callback и т.д.)
 *    содержит отправителя: $update->user->userId, а chat_id диалога — $update->chatId;
 *  - информация о пользователе — через getChatMembers(chatId, [userId]);
 *  - для диалога можно получить собеседника и через getChat(): $chat->dialogWithUser.
 *
 * Отправка сообщения именно пользователю — Recipient(userId: ...) (без chat_id).
 */

$client = max_client();

// id диалога и пользователя (в реальном боте приходят из апдейта)
$chatId = 123456789;
$userId = 12345;

$result = $client->getChatMembers($chatId, [$userId]);
$member = $result->members[0] ?? null;

if ($member === null) {
    fwrite(STDERR, "User not found\n");
    exit(1);
}

printf(
    "User: %s %s (@%s), admin: %s, avatar: %s\n",
    $member->firstName,
    $member->lastName ?? '',
    $member->username ?? '-',
    $member->isAdmin ? 'yes' : 'no',
    $member->avatarUrl ?? '-',
);

$client->sendMessage(
    new Recipient(userId: $userId),
    new NewMessageBody(text: 'Привет, ' . $member->firstName . '!'),
);
