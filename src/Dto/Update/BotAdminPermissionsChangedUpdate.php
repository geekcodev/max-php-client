<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class BotAdminPermissionsChangedUpdate extends Update
{
    /**
     * @param list<ChatAdminPermission>|null $permissions
     */
    public function __construct(
        int $timestamp,
        public int $chatId,
        public int $userId,
        public int $botId,
        public bool $isChannel,
        public bool $isAdmin,
        public ?array $permissions = null,
    ) {
        parent::__construct('bot_admin_permissions_changed', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            chatId: Json::requiredInt($data, 'chat_id'),
            userId: Json::requiredInt($data, 'user_id'),
            botId: Json::requiredInt($data, 'bot_id'),
            isChannel: Json::requiredBool($data, 'is_channel'),
            isAdmin: Json::requiredBool($data, 'is_admin'),
            permissions: self::permissions($data),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
            'bot_id' => $this->botId,
            'is_channel' => $this->isChannel,
            'is_admin' => $this->isAdmin,
            'permissions' => $this->permissions === null
                ? null
                : array_map(static fn (ChatAdminPermission $permission): string => $permission->value, $this->permissions),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
