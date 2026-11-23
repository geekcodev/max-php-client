<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class CommentRemovedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public string $messageId,
        public int $userId,
        public string $postId,
    ) {
        parent::__construct('comment_removed', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            messageId: Json::requiredString($data, 'message_id'),
            userId: Json::requiredInt($data, 'user_id'),
            postId: Json::requiredString($data, 'post_id'),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'post_id' => $this->postId,
        ];
    }
}
