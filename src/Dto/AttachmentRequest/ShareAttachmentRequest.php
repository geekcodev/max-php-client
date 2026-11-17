<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\ShareAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class ShareAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public ShareAttachmentPayload $payload,
    ) {
        parent::__construct('share');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: ShareAttachmentPayload::fromArray(Json::requiredArray($data, 'payload')),
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
