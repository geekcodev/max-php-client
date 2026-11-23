<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class BotStoppedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public User $user,
        public ?string $userLocale = null,
    ) {
        parent::__construct('bot_stopped', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            user: self::requiredUser($data),
            userLocale: Json::string($data, 'user_locale'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'user_locale' => $this->userLocale,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
