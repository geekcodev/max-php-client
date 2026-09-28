<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatAdmin
{
    /**
     * @param list<ChatAdminPermission> $permissions
     */
    public function __construct(
        public int $userId,
        public array $permissions,
        public ?string $alias = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: Json::requiredInt($data, 'user_id'),
            permissions: Json::map($data, 'permissions', static fn (mixed $value): ChatAdminPermission => ChatAdminPermission::fromValue(
                \is_string($value) ? $value : throw new InvalidResponseException('Admin permission must be a string.'),
            )) ?? [],
            alias: Json::string($data, 'alias'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'permissions' => array_map(
                static fn (ChatAdminPermission $permission): string => $permission->value,
                $this->permissions,
            ),
            'alias' => $this->alias,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
