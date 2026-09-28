<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Пересланный или отвеченный комментарий. В отличие от `LinkedMessage`, содержимое
 * связанного сообщения — это `CommentMessageBody`: у комментариев нет вложений.
 */
readonly class CommentLinkedMessage
{
    public function __construct(
        public string $type,
        public CommentMessageBody $message,
        public ?User $sender = null,
        public ?int $chatId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        if (!\is_array($messageData)) {
            throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Field "message" must be an object.');
        }

        $senderData = $data['sender'] ?? null;

        return new self(
            type: Json::requiredString($data, 'type'),
            message: CommentMessageBody::fromArray($messageData),
            sender: \is_array($senderData) ? User::fromArray($senderData) : null,
            chatId: Json::tolerantInt($data, 'chat_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'message' => $this->message->toArray(),
            'sender' => $this->sender?->toArray(),
            'chat_id' => $this->chatId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
