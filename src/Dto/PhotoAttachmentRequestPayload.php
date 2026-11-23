<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class PhotoAttachmentRequestPayload
{
    /**
     * @param array<mixed>|null $photos
     */
    public function __construct(
        public ?string $token = null,
        public ?string $url = null,
        public ?array $photos = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            token: Json::string($data, 'token'),
            url: Json::string($data, 'url'),
            photos: Json::array_($data, 'photos'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'token' => $this->token,
            'url' => $this->url,
            'photos' => $this->photos,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
