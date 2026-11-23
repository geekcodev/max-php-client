<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class PhotoAttachment extends Attachment
{
    public function __construct(
        public PhotoAttachmentPayload $payload,
    ) {
        parent::__construct('image');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: PhotoAttachmentPayload::fromArray(Json::requiredArray($data, 'payload')),
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
