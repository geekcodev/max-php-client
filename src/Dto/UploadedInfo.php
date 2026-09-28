<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Данные загруженного файла — ответ `POST /uploads` и `payload` вложений `video`,
 * `audio`, `file` при отправке сообщения. `url` обязателен, `token` приходит не
 * на каждом шаге загрузки.
 */
readonly class UploadedInfo extends UploadResult
{
    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
            token: Json::string($data, 'token'),
        );
    }
}
