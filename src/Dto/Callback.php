<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class Callback
{
    public function __construct(
        public string $callbackId,
        public ?string $payload = null,
        public ?Message $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;

        return new self(
            callbackId: Json::requiredString($data, 'callback_id'),
            payload: Json::string($data, 'payload'),
            message: \is_array($messageData) ? Message::fromArray($messageData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'callback_id' => $this->callbackId,
            'payload' => $this->payload,
            'message' => $this->message?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
