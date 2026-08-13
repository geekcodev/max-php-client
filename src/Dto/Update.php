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
        public ?User $user = null,
        public ?int $chatId = null,
        public ?bool $isChannel = null,
        public ?Message $message = null,
        public ?Callback $callback = null,
        public ?string $userLocale = null,
        public ?string $title = null,
        public ?string $payload = null,
        public ?int $mutedUntil = null,
        public ?string $messageId = null,
        public ?int $userId = null,
        public ?int $inviterId = null,
        public ?int $adminId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        $callbackData = $data['callback'] ?? null;

        $userData = $data['user'] ?? null;
        if (!\is_array($userData) && \is_array($messageData)) {
            $userData = $messageData['sender'] ?? null;
        }
        if (!\is_array($userData) && \is_array($callbackData) && \is_array($callbackData['message'] ?? null)) {
            $userData = $callbackData['message']['sender'] ?? null;
        }

        $chatId = Json::int($data, 'chat_id');
        if ($chatId === null && \is_array($messageData) && \is_array($messageData['recipient'] ?? null)) {
            $chatId = Json::int($messageData['recipient'], 'chat_id');
        }
        if ($chatId === null && \is_array($callbackData) && \is_array($callbackData['message'] ?? null)) {
            $recipient = $callbackData['message']['recipient'] ?? null;
            if (\is_array($recipient)) {
                $chatId = Json::int($recipient, 'chat_id');
            }
        }

        return new self(
            updateType: Json::enum(UpdateType::class, $data, 'update_type')
                ?? throw new InvalidResponseException('Field "update_type" must be a string.'),
            timestamp: Json::requiredInt($data, 'timestamp'),
            user: \is_array($userData) ? User::fromArray($userData) : null,
            chatId: $chatId,
            isChannel: Json::bool($data, 'is_channel'),
            message: \is_array($messageData) ? Message::fromArray($messageData) : null,
            callback: \is_array($callbackData) ? Callback::fromArray($callbackData) : null,
            userLocale: Json::string($data, 'user_locale'),
            title: Json::string($data, 'title'),
            payload: Json::string($data, 'payload'),
            mutedUntil: Json::int($data, 'muted_until'),
            messageId: Json::string($data, 'message_id'),
            userId: Json::int($data, 'user_id'),
            inviterId: Json::int($data, 'inviter_id'),
            adminId: Json::int($data, 'admin_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'update_type' => $this->updateType->value,
            'timestamp' => $this->timestamp,
            'chat_id' => $this->chatId,
            'user' => $this->user?->toArray(),
            'is_channel' => $this->isChannel,
            'message' => $this->message?->toArray(),
            'callback' => $this->callback?->toArray(),
            'user_locale' => $this->userLocale,
            'title' => $this->title,
            'payload' => $this->payload,
            'muted_until' => $this->mutedUntil,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'inviter_id' => $this->inviterId,
            'admin_id' => $this->adminId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
