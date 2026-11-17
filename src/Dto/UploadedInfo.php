<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Данные загруженного файла для payload вложений `video`, `audio`, `file`.
 * Ответ `POST /uploads` — `UploadEndpoint` (url + token).
 */
readonly class UploadedInfo
{
    public function __construct(
        public string $token,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(token: Json::requiredString($data, 'token'));
    }

    public function toArray(): array
    {
        return ['token' => $this->token];
    }
}
