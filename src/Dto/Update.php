<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Dto\Update\BotAddedToChatUpdate;
use GeekCo\MaxPhpClient\Dto\Update\BotAdminPermissionsChangedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\BotRemovedFromChatUpdate;
use GeekCo\MaxPhpClient\Dto\Update\BotStartedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\BotStoppedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\ChatTitleChangedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\CommentCreatedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\CommentEditedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\CommentRemovedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\DialogClearedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\DialogMutedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\DialogRemovedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\DialogUnmutedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\MessageCallbackUpdate;
use GeekCo\MaxPhpClient\Dto\Update\MessageCreatedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\MessageEditedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\MessageRemovedUpdate;
use GeekCo\MaxPhpClient\Dto\Update\UserAddedToChatUpdate;
use GeekCo\MaxPhpClient\Dto\Update\UserRemovedFromChatUpdate;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Событие MAX. База хранит только дискриминатор и время; поля конкретного
 * события находятся в подтипе.
 */
abstract readonly class Update
{
    public function __construct(
        public string $updateType,
        public int $timestamp,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $updateType = Json::requiredString($data, 'update_type');

        return match ($updateType) {
            'message_created' => MessageCreatedUpdate::fromArray($data),
            'message_edited' => MessageEditedUpdate::fromArray($data),
            'message_removed' => MessageRemovedUpdate::fromArray($data),
            'message_callback' => MessageCallbackUpdate::fromArray($data),
            'comment_created' => CommentCreatedUpdate::fromArray($data),
            'comment_edited' => CommentEditedUpdate::fromArray($data),
            'comment_removed' => CommentRemovedUpdate::fromArray($data),
            'bot_added' => BotAddedToChatUpdate::fromArray($data),
            'bot_admin_permissions_changed' => BotAdminPermissionsChangedUpdate::fromArray($data),
            'bot_removed' => BotRemovedFromChatUpdate::fromArray($data),
            'user_added' => UserAddedToChatUpdate::fromArray($data),
            'user_removed' => UserRemovedFromChatUpdate::fromArray($data),
            'bot_started' => BotStartedUpdate::fromArray($data),
            'bot_stopped' => BotStoppedUpdate::fromArray($data),
            'chat_title_changed' => ChatTitleChangedUpdate::fromArray($data),
            'dialog_cleared' => DialogClearedUpdate::fromArray($data),
            'dialog_muted' => DialogMutedUpdate::fromArray($data),
            'dialog_unmuted' => DialogUnmutedUpdate::fromArray($data),
            'dialog_removed' => DialogRemovedUpdate::fromArray($data),
            default => throw new InvalidResponseException(
                sprintf('Unsupported update_type "%s".', $updateType),
            ),
        };
    }

    public function toArray(): array
    {
        return [
            'update_type' => $this->updateType,
            'timestamp' => $this->timestamp,
        ];
    }

    protected static function requiredUser(array $data, string $key = 'user'): User
    {
        return User::fromArray(Json::requiredArray($data, $key));
    }

    protected static function requiredMessage(array $data, string $key = 'message'): Message
    {
        return Message::fromArray(Json::requiredArray($data, $key));
    }

    protected static function requiredComment(array $data, string $key = 'message'): CommentMessage
    {
        return CommentMessage::fromArray(Json::requiredArray($data, $key));
    }

    /**
     * @return list<ChatAdminPermission>|null
     */
    protected static function permissions(array $data): ?array
    {
        return Json::map(
            $data,
            'permissions',
            static fn (mixed $item): ChatAdminPermission => ChatAdminPermission::fromValue(
                \is_string($item) ? $item : throw new InvalidResponseException('Admin permission must be a string.'),
            ),
        );
    }
}
