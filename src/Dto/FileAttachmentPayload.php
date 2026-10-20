<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Payload вложения `type=file`. Метаданные (`filename`, `size`) официальная
 * `schema.yaml` объявляет на верхнем уровне `FileAttachment`, а спека max-openapi
 * держит их в `payload`; приоритет — верхний уровень, `payload` — фолбэк
 * (см. `Attachment`, `docs/api-reference.md`, раздел 8).
 *
 * Легаси-поле `file_size` официальной схемой не подтверждено и сохраняется до v2.0.0.
 */
readonly class FileAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
        public ?int $fileSize = null,
        public ?string $filename = null,
        public ?int $size = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
            fileSize: Json::int($data, 'file_size'),
            filename: Json::string($data, 'filename'),
            size: Json::int($data, 'size'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
            'file_size' => $this->fileSize,
            'filename' => $this->filename,
            'size' => $this->size,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
