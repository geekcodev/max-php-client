<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class PhotoAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public PhotoAttachmentRequestPayload $payload,
    ) {
        parent::__construct('image');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: PhotoAttachmentRequestPayload::fromArray(Json::requiredArray($data, 'payload')),
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
