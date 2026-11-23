<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\AttachmentRequest;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class InlineKeyboardAttachmentRequest extends AttachmentRequest
{
    public function __construct(
        public InlineKeyboardAttachmentRequestPayload $payload,
    ) {
        parent::__construct('inline_keyboard');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: InlineKeyboardAttachmentRequestPayload::fromArray(Json::requiredArray($data, 'payload')),
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
