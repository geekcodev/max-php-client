<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\MediaAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\VideoThumbnail;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class VideoAttachment extends Attachment
{
    public function __construct(
        public MediaAttachmentPayload $payload,
        public ?VideoThumbnail $thumbnail = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $duration = null,
    ) {
        parent::__construct('video');
    }

    public static function fromArray(array $data): self
    {
        $payloadData = Json::requiredArray($data, 'payload');
        $thumbnailData = $data['thumbnail'] ?? $payloadData['thumbnail'] ?? null;

        return new self(
            payload: MediaAttachmentPayload::fromArray($payloadData),
            thumbnail: \is_array($thumbnailData) ? VideoThumbnail::fromArray($thumbnailData) : null,
            width: Json::int($data, 'width') ?? Json::int($payloadData, 'width'),
            height: Json::int($data, 'height') ?? Json::int($payloadData, 'height'),
            duration: Json::int($data, 'duration') ?? Json::int($payloadData, 'duration'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
            'thumbnail' => $this->thumbnail?->toArray(),
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
