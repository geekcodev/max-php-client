<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Security\WebAppContactVerifier;

require __DIR__ . '/bootstrap.php';

/**
 * Верификация контакта, полученного мини-приложением через window.WebApp.requestContact().
 *
 * Это отдельный механизм от контакта по кнопке `request_contact` (см. verify-contact.php):
 * здесь сервер MAX подписывает три поля payload'а key=value в алфавитном порядке ключей,
 * склеенных через \n (authDate, phone, userId), а phone идёт без «+»:
 *
 *   hash = HMAC-SHA256("authDate=<v>\nphone=<v>\nuserId=<v>", bot_token)
 *
 * В браузере мини-приложения вызов возвращает объект { phone, authDate, hash, userId }.
 * Он уходит на ваш бэкенд, и подпись проверяется там этим верификатором; номер берётся
 * только при успешной проверке. Сравнение константное, номер и payload не логируются.
 */

$accessToken = (string) getenv('MAX_API_TOKEN');

if ($accessToken === '') {
    fwrite(STDERR, "Set MAX_API_TOKEN (source .env) before running this example\n");
    exit(1);
}

// Данные, которые мини-приложение получает от requestContact(). В реальном коде они
// приходят с фронтенда; здесь подпись вычисляется локально тем же ключом, что и у API,
// чтобы пример был запускаемым.
$authDate = (string) time();
$phone = '79991234567'; // сервер подписывает только цифры, без «+»
$userId = '67890';
$payload = 'authDate=' . $authDate . "\nphone=" . $phone . "\nuserId=" . $userId;
$hash = hash_hmac('sha256', $payload, $accessToken);

$verifier = new WebAppContactVerifier(
    accessToken: $accessToken,
    maxAge: 86400, // replay-защита: подпись старше суток отклоняется (0 — не проверять)
);

if (!$verifier->verify($authDate, $phone, $userId, $hash)) {
    fwrite(STDERR, "Invalid WebApp contact hash\n");
    exit(1);
}

fwrite(STDOUT, "Contact verified\n");

// Подтверждённые значения: номер нормализован в цифры без «+», userId/authDate — int.
$contact = $verifier->resolve($authDate, $phone, $userId, $hash);
if ($contact !== null) {
    fwrite(STDOUT, sprintf(
        "user_id=%d auth_date=%d phone=%s\n",
        $contact->userId,
        $contact->authDate,
        $contact->phone,
    ));
}

// Подмена номера ломает подпись — контакт отклоняется.
fwrite(STDOUT, sprintf(
    "Подменённый номер: %s\n",
    $verifier->verify($authDate, '79000000000', $userId, $hash) ? 'ПРИНЯТ (ошибка!)' : 'отклонён',
));
