<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class ContactAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public ContactAttachmentRequestPayload $payload,
    ) {
        parent::__construct('contact');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: ContactAttachmentRequestPayload::fromArray(Json::requiredArray($data, 'payload')),
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
