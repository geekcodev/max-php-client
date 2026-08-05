<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class PhotoAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            width: Json::int($data, 'width'),
            height: Json::int($data, 'height'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'width' => $this->width,
            'height' => $this->height,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
