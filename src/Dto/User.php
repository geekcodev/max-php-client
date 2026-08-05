<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class User
{
    public function __construct(
        public int $userId,
        public string $firstName,
        public ?string $lastName,
        public ?string $username,
        public bool $isBot,
        public int $lastActivityTime,
        public ?string $name = null,
    ) {
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
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'username' => $this->username,
            'is_bot' => $this->isBot,
            'last_activity_time' => $this->lastActivityTime,
            'name' => $this->name,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
