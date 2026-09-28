<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Payload вложения `type=image`. В спецификации подтип называется
 * `PhotoAttachmentPayload`; имя класса сохранено для совместимости, состав полей
 * соответствует спеке.
 */
readonly class ImageAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
        public ?int $photoId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
            photoId: Json::tolerantInt($data, 'photo_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
            'photo_id' => $this->photoId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
