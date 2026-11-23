<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class StickerAttachmentPayload
{
    public function __construct(
        public string $url,
        public string $code,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
            code: Json::requiredString($data, 'code'),
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'code' => $this->code,
        ];
    }
}
