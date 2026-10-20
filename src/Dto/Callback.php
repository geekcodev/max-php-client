<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Нажатие кнопки `callback`. Спека объявляет обязательными `timestamp`, `callback_id`
 * и `user`; в `Update` пользователь события берётся из `callback.user`, если его нет
 * ни на верхнем уровне, ни в `message.sender`.
 *
 * Поле `message` официальной схемой не объявлено, но приходит в проде — сохраняется
 * (см. `docs/api-reference.md`, раздел 9).
 */
readonly class Callback
{
    public function __construct(
        public string $callbackId,
        public ?string $payload = null,
        public ?Message $message = null,
        public ?int $timestamp = null,
        public ?User $user = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        $userData = $data['user'] ?? null;

        return new self(
            callbackId: Json::requiredString($data, 'callback_id'),
            payload: Json::string($data, 'payload'),
            message: \is_array($messageData) ? Message::fromArray($messageData) : null,
            timestamp: Json::int($data, 'timestamp'),
            user: \is_array($userData) ? User::fromArray($userData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'callback_id' => $this->callbackId,
            'payload' => $this->payload,
            'message' => $this->message?->toArray(),
            'timestamp' => $this->timestamp,
            'user' => $this->user?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
