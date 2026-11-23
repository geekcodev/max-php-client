<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\UploadedInfo;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class VideoAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public UploadedInfo $payload,
    ) {
        parent::__construct('video');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: UploadedInfo::fromArray(Json::requiredArray($data, 'payload')),
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
