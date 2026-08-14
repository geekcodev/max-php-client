<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatMember extends UserWithPhoto
{
    /**
     * @param list<ChatAdminPermission>|null $permissions
     */
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
        public int $lastAccessTime = 0,
        public bool $isOwner = false,
        public bool $isAdmin = false,
        public int $joinTime = 0,
        public ?array $permissions = null,
        public ?string $alias = null,
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
            lastAccessTime: Json::requiredInt($data, 'last_access_time'),
            isOwner: Json::requiredBool($data, 'is_owner'),
            isAdmin: Json::requiredBool($data, 'is_admin'),
            joinTime: Json::requiredInt($data, 'join_time'),
            permissions: Json::map(
                $data,
                'permissions',
                static fn (mixed $item): ChatAdminPermission => self::permission(
                    \is_string($item) ? $item : throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException(
                        'Admin permission must be a string.',
                    ),
                ),
            ),
            alias: Json::string($data, 'alias'),
        );
    }

    private static function permission(string $value): ChatAdminPermission
    {
        $permission = ChatAdminPermission::tryFrom($value);
        if ($permission === null) {
            throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException(
                sprintf('Unsupported admin permission "%s".', $value),
            );
        }

        return $permission;
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'last_access_time' => $this->lastAccessTime,
            'is_owner' => $this->isOwner,
            'is_admin' => $this->isAdmin,
            'join_time' => $this->joinTime,
            'permissions' => $this->permissions === null
                ? null
                : array_map(static fn (ChatAdminPermission $permission): string => $permission->value, $this->permissions),
            'alias' => $this->alias,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
