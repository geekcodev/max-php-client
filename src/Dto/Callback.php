<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Нажатие кнопки `callback`. Сообщение, к которому относится нажатие, — в
 * `MessageCallbackUpdate::$message`; в `Callback` его нет.
 */
readonly class Callback
{
    public function __construct(
        public string $callbackId,
        public ?string $payload = null,
        public ?int $timestamp = null,
        public ?User $user = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $userData = $data['user'] ?? null;

        return new self(
            callbackId: Json::requiredString($data, 'callback_id'),
            payload: Json::string($data, 'payload'),
            timestamp: Json::int($data, 'timestamp'),
            user: \is_array($userData) ? User::fromArray($userData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'callback_id' => $this->callbackId,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp,
            'user' => $this->user?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
