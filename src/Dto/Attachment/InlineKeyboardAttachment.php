<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\Keyboard;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class InlineKeyboardAttachment extends Attachment
{
    public function __construct(
        public Keyboard $payload,
    ) {
        parent::__construct('inline_keyboard');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: Keyboard::fromArray(Json::requiredArray($data, 'payload')),
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
