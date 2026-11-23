<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class DialogMutedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public int $chatId,
        public User $user,
        public int $mutedUntil,
        public ?string $userLocale = null,
    ) {
        parent::__construct('dialog_muted', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            user: self::requiredUser($data),
            mutedUntil: Json::requiredInt($data, 'muted_until'),
            userLocale: Json::string($data, 'user_locale'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'muted_until' => $this->mutedUntil,
            'user_locale' => $this->userLocale,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
