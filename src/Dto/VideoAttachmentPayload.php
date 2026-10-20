<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Payload вложения `type=video`. Метаданные (`duration`, `width`, `height`,
 * `thumbnail`) официальная `schema.yaml` объявляет на верхнем уровне
 * `VideoAttachment`, а спека max-openapi держит их в `payload`; приоритет —
 * верхний уровень, `payload` — фолбэк (см. `Attachment`,
 * `docs/api-reference.md`, раздел 8).
 */
readonly class VideoAttachmentPayload
{
    public function __construct(
        public ?VideoUrls $url = null,
        public ?string $token = null,
        public ?int $duration = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?PhotoAttachmentPayload $thumbnail = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $urlData = $data['url'] ?? null;
        $thumbnailData = $data['thumbnail'] ?? null;

        return new self(
            url: \is_array($urlData) ? VideoUrls::fromArray($urlData) : null,
            token: Json::string($data, 'token'),
            duration: Json::int($data, 'duration'),
            width: Json::int($data, 'width'),
            height: Json::int($data, 'height'),
            thumbnail: \is_array($thumbnailData) ? PhotoAttachmentPayload::fromArray($thumbnailData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url?->toArray(),
            'token' => $this->token,
            'duration' => $this->duration,
            'width' => $this->width,
            'height' => $this->height,
            'thumbnail' => $this->thumbnail?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
