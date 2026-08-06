<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Security\ContactVerifier;

require __DIR__ . '/bootstrap.php';

/**
 * Верификация контакта из кнопки request_contact.
 * $vcfInfo и $hash приходят в апдейте после нажатия кнопки
 * (в спецификации: hash = HMAC-SHA256(access_token, vcf_info),
 *  в vcf_info последовательности \r\n заменяются реальными переносами).
 */

$verifier = new ContactVerifier(accessToken: (string) getenv('MAX_API_TOKEN'));

$vcfInfo = "BEGIN:VCARD\r\nFN:Иван Иванов\r\nTEL:+7...\r\nEND:VCARD";
$hash = '...'; // значение из апдейта

if ($verifier->verify($vcfInfo, $hash)) {
    fwrite(STDOUT, "Contact verified\n");
} else {
    fwrite(STDERR, "Invalid contact hash\n");
    exit(1);
}
