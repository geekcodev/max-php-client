<?php

declare(strict_types=1);

use GeekCo\MaxPhpClient\Dto\Attachment\ContactAttachment;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\Update\MessageCreatedUpdate;
use GeekCo\MaxPhpClient\Security\ContactPhoneExtractor;
use GeekCo\MaxPhpClient\Security\ContactVerifier;

require __DIR__ . '/bootstrap.php';

/**
 * Верификация контакта, присланного по кнопке `request_contact`, и получение номера.
 *
 * Схема работы бота: отправить сообщение с кнопкой
 * `new RequestContactButton('Поделиться контактом')` внутри `InlineKeyboardAttachmentRequest`
 * (см. examples/inline-keyboard.php). Когда пользователь нажмёт кнопку, придёт апдейт
 * `message_created`, у которого в `message.body.attachments` есть `ContactAttachment`
 * с `payload` — это и есть контакт.
 *
 * Порядок действий:
 *   1. найти вложение contact и взять его payload (ContactAttachmentPayload);
 *   2. проверить подлинность: hash = HMAC-SHA256(access_token, vcf_info) по сырым байтам;
 *   3. только после успешной проверки брать номер.
 *
 * Проверять хэш важно: без неё номер из vcf_info пришёл бы от кого угодно.
 */

$accessToken = (string) getenv('MAX_API_TOKEN');

if ($accessToken === '') {
    fwrite(STDERR, "Set MAX_API_TOKEN (source .env) before running this example\n");
    exit(1);
}

// vCard в том виде, в каком его присылает MAX: реальные CRLF, завершающий перевод строки,
// `TEL` раньше `FN`, имя в UTF-8. Значение TEL возвращается как есть, в реальных данных — без `+`.
$vcfInfo = "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:ez-vcard 0.10.3\r\n"
    . "TEL;TYPE=cell:79250000000\r\nFN:Тест\r\nEND:VCARD\r\n";

// Хэш подписывает MAX, в боте он приходит в payload из апдейта. Здесь он вычисляется
// локально только для того, чтобы пример был запускаемым: тем же ключом и по тем же
// сырым байтам, что и делает API.
$hash = hash_hmac('sha256', $vcfInfo, $accessToken);

// Апдейт `message_created` с вложением контакта. В боте сюда попадает реальный апдейт
// из WebhookHandler::decode() или LongPollingRunner.
$update = Update::fromArray([
    'update_type' => 'message_created',
    'timestamp' => 1771409719000,
    'message' => [
        'sender' => ['user_id' => 12345, 'first_name' => 'Тест', 'is_bot' => false],
        'recipient' => ['user_id' => 12345],
        'timestamp' => 1771409719000,
        'body' => [
            'mid' => 'm5p7e4i9oS',
            'seq' => 1,
            'attachments' => [
                [
                    'type' => 'contact',
                    'payload' => ['hash' => $hash, 'vcf_info' => $vcfInfo],
                ],
            ],
        ],
    ],
]);

if (!$update instanceof MessageCreatedUpdate) {
    fwrite(STDERR, "Expected message_created update\n");
    exit(1);
}

$contact = null;
foreach ($update->message->body?->attachments ?? [] as $attachment) {
    if ($attachment instanceof ContactAttachment) {
        $contact = $attachment->payload;
        break;
    }
}

if ($contact === null) {
    fwrite(STDERR, "No contact attachment in update\n");
    exit(1);
}

$verifier = new ContactVerifier(accessToken: $accessToken);

if (!$verifier->verify((string) $contact->vcfInfo, $contact->hash)) {
    fwrite(STDERR, "Invalid contact hash\n");
    exit(1);
}

fwrite(STDOUT, "Contact verified\n");

// Во входящем payload поля `vcf_phone` нет — оно объявлено только в исходящем
// ContactAttachmentRequestPayload. Телефон берётся из подписанного `vcf_info`.
$phone = ContactPhoneExtractor::fromVcf((string) $contact->vcfInfo);

if ($phone === null) {
    fwrite(STDERR, "Contact has no phone\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Phone: %s\n", $phone));
// Нормализации в ядре нет: значение приходит как прислал MAX, в реальных данных без `+`.
// Если номер служит ключом (сверка с CRM, дедупликация, привязка лида), единое правило
// формы применяйте у себя, ко всем точкам ввода, включая формы.

// Проверка обязательна: подпись покрывает сырые байты vcf_info, поэтому подмена номера
// ломает хэш и контакт отбрасывается.
$tampered = str_replace('79250000000', '79000000000', (string) $contact->vcfInfo);

fwrite(STDOUT, sprintf(
    "Подменённый номер: %s\n",
    $verifier->verify($tampered, $contact->hash) ? 'ПРИНЯТ (ошибка!)' : 'отклонён',
));

