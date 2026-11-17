<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class StickerAttachmentRequestPayload
{
    public function __construct(
        public string $code,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(code: Json::requiredString($data, 'code'));
    }

    public function toArray(): array
    {
        return ['code' => $this->code];
    }
}
