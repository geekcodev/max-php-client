<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class FileAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
        public ?int $fileSize = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
            fileSize: Json::int($data, 'file_size'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
            'file_size' => $this->fileSize,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
