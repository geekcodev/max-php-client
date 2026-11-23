<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\StickerAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class StickerAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public StickerAttachmentRequestPayload $payload,
    ) {
        parent::__construct('sticker');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: StickerAttachmentRequestPayload::fromArray(Json::requiredArray($data, 'payload')),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
        ];
    }
}
