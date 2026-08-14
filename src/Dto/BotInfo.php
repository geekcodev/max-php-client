<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class BotInfo extends UserWithPhoto
{
    public function __construct(
        int $userId,
        string $firstName,
        ?string $lastName,
        ?string $username,
        bool $isBot,
        ?int $lastActivityTime = null,
        ?string $name = null,
        ?string $description = null,
        ?string $avatarUrl = null,
        ?string $fullAvatarUrl = null,
        /** @var list<BotCommand>|null */
        public ?array $commands = null,
    ) {
        parent::__construct(
            $userId,
            $firstName,
            $lastName,
            $username,
            $isBot,
            $lastActivityTime,
            $name,
            $description,
            $avatarUrl,
            $fullAvatarUrl,
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
            lastActivityTime: Json::int($data, 'last_activity_time'),
            name: Json::string($data, 'name'),
            description: Json::string($data, 'description'),
            avatarUrl: Json::string($data, 'avatar_url'),
            fullAvatarUrl: Json::string($data, 'full_avatar_url'),
            commands: Json::map($data, 'commands', static fn (mixed $item): BotCommand => BotCommand::fromArray((array) $item)),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'commands' => $this->commands === null
                ? null
                : array_map(static fn (BotCommand $command): array => $command->toArray(), $this->commands),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
