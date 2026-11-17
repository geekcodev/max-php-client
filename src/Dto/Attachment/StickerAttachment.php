<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\StickerAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class StickerAttachment extends Attachment
{
    public function __construct(
        public StickerAttachmentPayload $payload,
        public ?int $width = null,
        public ?int $height = null,
    ) {
        parent::__construct('sticker');
    }

    public static function fromArray(array $data): self
    {
        $payloadData = Json::requiredArray($data, 'payload');

        return new self(
            payload: StickerAttachmentPayload::fromArray($payloadData),
            width: Json::int($data, 'width') ?? Json::int($payloadData, 'width'),
            height: Json::int($data, 'height') ?? Json::int($payloadData, 'height'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
            'width' => $this->width,
            'height' => $this->height,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
