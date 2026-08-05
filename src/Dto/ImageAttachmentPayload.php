<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ImageAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
