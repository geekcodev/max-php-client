<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Security\WebAppDataValidator;

require __DIR__ . '/bootstrap.php';

/**
 * Верификация стартовых данных мини-приложения (window.WebApp.initData).
 *
 * Валидация защищает от подделки данных пользователя. Алгоритм:
 *   secret_key = HMAC-SHA256('WebAppData', bot_token)
 *   signature  = hex(HMAC-SHA256(secret_key, launch_params))
 *
 * Строку initData можно взять из window.WebApp.initData или извлечь из URL
 * открытия мини-приложения (verifyFromUrl). Подробнее:
 *   https://dev.max.ru/docs/webapps/validation
 */

$validator = new WebAppDataValidator(accessToken: (string) getenv('MAX_API_TOKEN'));

// Строка initData из window.WebApp.initData (значение параметра WebAppData в URL).
$initData = 'auth_date=1771409719&chat=%7B...%7D&user=%7B...%7D&query_id=...&hash=...';

if ($validator->verify($initData)) {
    fwrite(STDOUT, "WebApp data verified\n");
} else {
    fwrite(STDERR, "Invalid WebApp data hash\n");
    exit(1);
}
