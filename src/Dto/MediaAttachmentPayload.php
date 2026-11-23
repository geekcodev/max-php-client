<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class MediaAttachmentPayload
{
    public function __construct(
        public string $url,
        public string $token,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
            token: Json::requiredString($data, 'token'),
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'token' => $this->token,
        ];
    }
}
