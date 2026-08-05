<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class UserWithPhoto extends User
{
    public function __construct(
        int $userId,
        string $firstName,
        ?string $lastName,
        ?string $username,
        bool $isBot,
        int $lastActivityTime,
        ?string $name = null,
        public ?string $description = null,
        public ?string $avatarUrl = null,
        public ?string $fullAvatarUrl = null,
    ) {
        parent::__construct(
            $userId,
            $firstName,
            $lastName,
            $username,
            $isBot,
            $lastActivityTime,
            $name,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: Json::requiredInt($data, 'user_id'),
            firstName: Json::requiredString($data, 'first_name'),
            lastName: Json::string($data, 'last_name'),
            username: Json::string($data, 'username'),
            isBot: Json::requiredBool($data, 'is_bot'),
            lastActivityTime: Json::requiredInt($data, 'last_activity_time'),
            name: Json::string($data, 'name'),
            description: Json::string($data, 'description'),
            avatarUrl: Json::string($data, 'avatar_url'),
            fullAvatarUrl: Json::string($data, 'full_avatar_url'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'description' => $this->description,
            'avatar_url' => $this->avatarUrl,
            'full_avatar_url' => $this->fullAvatarUrl,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
