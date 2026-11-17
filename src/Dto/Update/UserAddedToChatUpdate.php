<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class UserAddedToChatUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public User $user,
        public bool $isChannel,
        public ?int $inviterId = null,
    ) {
        parent::__construct('user_added', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            user: self::requiredUser($data),
            isChannel: Json::requiredBool($data, 'is_channel'),
            inviterId: Json::int($data, 'inviter_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'is_channel' => $this->isChannel,
            'inviter_id' => $this->inviterId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
