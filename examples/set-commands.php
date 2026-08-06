<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\BotCommand;

require __DIR__ . '/bootstrap.php';

/**
 * Установка команд бота (максимум 32; пустой массив — удалить все).
 */

$client = max_client();

$client->editBotCommands([
    new BotCommand(name: 'start', description: 'Начать работу с ботом'),
    new BotCommand(name: 'help', description: 'Показать справку'),
]);
