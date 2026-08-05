<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\UpdateType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class Update
{
    public function __construct(
        public UpdateType $updateType,
        public int $timestamp,
        public User $user,
        public ?int $chatId = null,
        public ?bool $isChannel = null,
        public ?Message $message = null,
        public ?Callback $callback = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $userData = $data['user'] ?? null;
        $messageData = $data['message'] ?? null;
        $callbackData = $data['callback'] ?? null;

        return new self(
            updateType: Json::enum(UpdateType::class, $data, 'update_type')
                ?? throw new InvalidResponseException('Field "update_type" must be a string.'),
            timestamp: Json::requiredInt($data, 'timestamp'),
            user: \is_array($userData)
                ? User::fromArray($userData)
                : throw new InvalidResponseException('Field "user" must be an object.'),
            chatId: Json::int($data, 'chat_id'),
            isChannel: Json::bool($data, 'is_channel'),
            message: \is_array($messageData) ? Message::fromArray($messageData) : null,
            callback: \is_array($callbackData) ? Callback::fromArray($callbackData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'update_type' => $this->updateType->value,
            'timestamp' => $this->timestamp,
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'is_channel' => $this->isChannel,
            'message' => $this->message?->toArray(),
            'callback' => $this->callback?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
