<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class ContactAttachment extends Attachment
{
    public function __construct(
        public ContactAttachmentPayload $payload,
    ) {
        parent::__construct('contact');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: ContactAttachmentPayload::fromArray(Json::requiredArray($data, 'payload')),
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
