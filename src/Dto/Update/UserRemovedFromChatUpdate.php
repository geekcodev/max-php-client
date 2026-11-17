<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class UserRemovedFromChatUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public User $user,
        public bool $isChannel,
        public ?int $adminId = null,
    ) {
        parent::__construct('user_removed', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            user: self::requiredUser($data),
            isChannel: Json::requiredBool($data, 'is_channel'),
            adminId: Json::int($data, 'admin_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'is_channel' => $this->isChannel,
            'admin_id' => $this->adminId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
