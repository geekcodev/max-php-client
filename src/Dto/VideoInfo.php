<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class VideoInfo
{
    public function __construct(
        public string $token,
        public ?VideoUrls $urls = null,
        public ?PhotoAttachmentPayload $thumbnail = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $duration = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $urlsData = $data['urls'] ?? null;
        $thumbnailData = $data['thumbnail'] ?? null;

        return new self(
            token: Json::requiredString($data, 'token'),
            urls: \is_array($urlsData) ? VideoUrls::fromArray($urlsData) : null,
            thumbnail: \is_array($thumbnailData) ? PhotoAttachmentPayload::fromArray($thumbnailData) : null,
            width: Json::int($data, 'width'),
            height: Json::int($data, 'height'),
            duration: Json::int($data, 'duration'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'token' => $this->token,
            'urls' => $this->urls?->toArray(),
            'thumbnail' => $this->thumbnail?->toArray(),
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
