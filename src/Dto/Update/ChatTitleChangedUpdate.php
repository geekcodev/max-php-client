<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class ChatTitleChangedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public User $user,
        public string $title,
    ) {
        parent::__construct('chat_title_changed', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            user: self::requiredUser($data),
            title: Json::requiredString($data, 'title'),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'title' => $this->title,
        ];
    }
}
