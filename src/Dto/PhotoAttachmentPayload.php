<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class PhotoAttachmentPayload
{
    public function __construct(
        public int $photoId,
        public string $token,
        public string $url,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            photoId: Json::requiredInt($data, 'photo_id'),
            token: Json::requiredString($data, 'token'),
            url: Json::requiredString($data, 'url'),
        );
    }

    public function toArray(): array
    {
        return [
            'photo_id' => $this->photoId,
            'token' => $this->token,
            'url' => $this->url,
        ];
    }
}
