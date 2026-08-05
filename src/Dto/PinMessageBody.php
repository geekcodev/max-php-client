<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class PinMessageBody
{
    public function __construct(
        public string $messageId,
        public ?bool $notify = null,
    ) {
    }

    public static function create(string $messageId, ?bool $notify = null): self
    {
        return new self(
            messageId: $messageId,
            notify: $notify,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            messageId: Json::requiredString($data, 'message_id'),
            notify: Json::bool($data, 'notify'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'message_id' => $this->messageId,
            'notify' => $this->notify,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
