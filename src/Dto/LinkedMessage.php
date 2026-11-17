<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\MessageLinkType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class LinkedMessage
{
    public function __construct(
        public MessageLinkType $type,
        public ?User $sender,
        public ?int $senderId,
        public string $mid,
        public ?string $chat = null,
        public ?MessageBody $message = null,
        public ?int $chatId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        $message = \is_array($messageData) ? MessageBody::fromArray($messageData) : null;
        $senderData = $data['sender'] ?? null;
        $sender = \is_array($senderData) ? User::fromArray($senderData) : null;
        $senderId = $sender !== null ? $sender->userId : Json::tolerantInt($data, 'sender');
        $mid = Json::string($data, 'mid');
        if ($mid === null && $message !== null) {
            $mid = $message->mid;
        }
        if ($mid === null) {
            throw new InvalidResponseException('Field "mid" must be a string.');
        }

        $chatId = Json::tolerantInt($data, 'chat_id');
        $chat = Json::string($data, 'chat');
        if ($chatId === null && $chat !== null && preg_match('/^-?\d+$/', $chat) === 1) {
            $chatId = (int) $chat;
        }

        return new self(
            type: Json::enum(MessageLinkType::class, $data, 'type')
                ?? throw new InvalidResponseException('Field "type" must be a string.'),
            sender: $sender,
            senderId: $senderId,
            mid: $mid,
            chat: $chat ?? ($chatId === null ? null : (string) $chatId),
            message: $message,
            chatId: $chatId,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'sender' => $this->sender?->toArray() ?? $this->senderId,
            'mid' => $this->mid,
            'message' => $this->message?->toArray(),
            'chat_id' => $this->chatId,
            'chat' => $this->chat,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
