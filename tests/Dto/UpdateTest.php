<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\Update;
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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UpdateTest extends TestCase
{
    private const array USER = [
        'user_id' => 7,
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'username' => 'alice',
        'is_bot' => false,
        'last_activity_time' => 1000,
    ];

    private const array MESSAGE = [
        'sender' => self::USER,
        'recipient' => ['chat_id' => 5, 'user_id' => 7, 'chat_type' => 'dialog'],
        'timestamp' => 1000,
        'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hello'],
    ];

    private const array COMMENT = [
        'sender' => self::USER,
        'recipient' => ['chat_id' => 5, 'chat_type' => 'channel', 'post_id' => 'post1'],
        'timestamp' => 1000,
        'body' => ['mid' => 'c1', 'seq' => 2, 'text' => 'comment'],
    ];

    private function payload(string $type, array $extra = []): array
    {
        return ['update_type' => $type, 'timestamp' => 1000, ...$extra];
    }

    #[Test]
    public function it_parses_message_created(): void
    {
        $update = Update::fromArray($this->payload('message_created', [
            'message' => self::MESSAGE,
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(MessageCreatedUpdate::class, $update);
        $this->assertSame('message_created', $update->updateType);
        $this->assertSame('hello', $update->message->body->text);
        $this->assertSame('ru', $update->userLocale);
        $this->assertSame('message_created', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_message_edited(): void
    {
        $update = Update::fromArray($this->payload('message_edited', ['message' => self::MESSAGE]));

        $this->assertInstanceOf(MessageEditedUpdate::class, $update);
        $this->assertSame('m1', $update->message->body->mid);
        $this->assertArrayHasKey('message', $update->toArray());
    }

    #[Test]
    public function it_parses_message_removed(): void
    {
        $update = Update::fromArray($this->payload('message_removed', [
            'chat_id' => 5,
            'message_id' => 'm1',
            'user_id' => 7,
        ]));

        $this->assertInstanceOf(MessageRemovedUpdate::class, $update);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('m1', $update->messageId);
        $this->assertSame(7, $update->userId);
        $this->assertSame('message_removed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_reads_message_callback_message_from_callback_fallback(): void
    {
        $update = Update::fromArray($this->payload('message_callback', [
            'callback' => [
                'callback_id' => 'c1',
                'payload' => 'p',
                'timestamp' => 1000,
                'user' => self::USER,
                'message' => self::MESSAGE,
            ],
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(MessageCallbackUpdate::class, $update);
        $this->assertSame('c1', $update->callback->callbackId);
        $this->assertSame('hello', $update->message?->body?->text);
        $this->assertSame('ru', $update->userLocale);
        $this->assertArrayHasKey('callback', $update->toArray());
    }

    #[Test]
    public function it_parses_comment_created(): void
    {
        $update = Update::fromArray($this->payload('comment_created', ['message' => self::COMMENT]));

        $this->assertInstanceOf(CommentCreatedUpdate::class, $update);
        $this->assertSame('c1', $update->message->body->mid);
        $this->assertSame('post1', $update->message->recipient->postId);
        $this->assertSame('comment_created', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_comment_edited(): void
    {
        $update = Update::fromArray($this->payload('comment_edited', ['message' => self::COMMENT]));

        $this->assertInstanceOf(CommentEditedUpdate::class, $update);
        $this->assertSame('comment', $update->message->body->text);
        $this->assertArrayHasKey('message', $update->toArray());
    }

    #[Test]
    public function it_parses_comment_removed(): void
    {
        $update = Update::fromArray($this->payload('comment_removed', [
            'chat_id' => 5,
            'message_id' => 'c1',
            'user_id' => 7,
            'post_id' => 'post1',
        ]));

        $this->assertInstanceOf(CommentRemovedUpdate::class, $update);
        $this->assertSame('post1', $update->postId);
        $this->assertSame('comment_removed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_bot_added(): void
    {
        $update = Update::fromArray($this->payload('bot_added', [
            'chat_id' => 5,
            'user' => self::USER,
            'is_channel' => false,
        ]));

        $this->assertInstanceOf(BotAddedToChatUpdate::class, $update);
        $this->assertSame(7, $update->user->userId);
        $this->assertFalse($update->isChannel);
        $this->assertSame('bot_added', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_bot_admin_permissions_changed(): void
    {
        $update = Update::fromArray($this->payload('bot_admin_permissions_changed', [
            'chat_id' => 5,
            'user_id' => 7,
            'bot_id' => 42,
            'is_channel' => true,
            'is_admin' => false,
            'permissions' => ['read_all_messages', 'write'],
        ]));

        $this->assertInstanceOf(BotAdminPermissionsChangedUpdate::class, $update);
        $this->assertSame(42, $update->botId);
        $this->assertSame(
            [ChatAdminPermission::ReadAllMessages, ChatAdminPermission::Write],
            $update->permissions,
        );
        $this->assertSame(['read_all_messages', 'write'], $update->toArray()['permissions']);
    }

    #[Test]
    public function it_parses_bot_removed(): void
    {
        $update = Update::fromArray($this->payload('bot_removed', [
            'chat_id' => 5,
            'user' => self::USER,
            'is_channel' => false,
        ]));

        $this->assertInstanceOf(BotRemovedFromChatUpdate::class, $update);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('bot_removed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_user_added(): void
    {
        $update = Update::fromArray($this->payload('user_added', [
            'chat_id' => 5,
            'user' => self::USER,
            'inviter_id' => 3,
            'is_channel' => false,
        ]));

        $this->assertInstanceOf(UserAddedToChatUpdate::class, $update);
        $this->assertSame(3, $update->inviterId);
        $this->assertSame('user_added', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_user_removed(): void
    {
        $update = Update::fromArray($this->payload('user_removed', [
            'chat_id' => 5,
            'user' => self::USER,
            'admin_id' => 4,
            'is_channel' => false,
        ]));

        $this->assertInstanceOf(UserRemovedFromChatUpdate::class, $update);
        $this->assertSame(4, $update->adminId);
        $this->assertSame('user_removed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_bot_started(): void
    {
        $update = Update::fromArray($this->payload('bot_started', [
            'chat_id' => 5,
            'user' => self::USER,
            'payload' => 'deep-link',
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(BotStartedUpdate::class, $update);
        $this->assertSame('deep-link', $update->payload);
        $this->assertSame('ru', $update->userLocale);
        $this->assertSame('bot_started', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_bot_stopped(): void
    {
        $update = Update::fromArray($this->payload('bot_stopped', [
            'chat_id' => 5,
            'user' => self::USER,
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(BotStoppedUpdate::class, $update);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('bot_stopped', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_chat_title_changed(): void
    {
        $update = Update::fromArray($this->payload('chat_title_changed', [
            'chat_id' => 5,
            'user' => self::USER,
            'title' => 'New title',
        ]));

        $this->assertInstanceOf(ChatTitleChangedUpdate::class, $update);
        $this->assertSame('New title', $update->title);
        $this->assertSame('chat_title_changed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_dialog_cleared(): void
    {
        $update = Update::fromArray($this->payload('dialog_cleared', [
            'chat_id' => 5,
            'user' => self::USER,
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(DialogClearedUpdate::class, $update);
        $this->assertSame('ru', $update->userLocale);
        $this->assertSame('dialog_cleared', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_dialog_muted(): void
    {
        $update = Update::fromArray($this->payload('dialog_muted', [
            'chat_id' => 5,
            'user' => self::USER,
            'muted_until' => 2000,
            'user_locale' => 'ru',
        ]));

        $this->assertInstanceOf(DialogMutedUpdate::class, $update);
        $this->assertSame(2000, $update->mutedUntil);
        $this->assertSame('dialog_muted', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_dialog_unmuted(): void
    {
        $update = Update::fromArray($this->payload('dialog_unmuted', [
            'chat_id' => 5,
            'user' => self::USER,
        ]));

        $this->assertInstanceOf(DialogUnmutedUpdate::class, $update);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('dialog_unmuted', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_parses_dialog_removed(): void
    {
        $update = Update::fromArray($this->payload('dialog_removed', [
            'chat_id' => 5,
            'user' => self::USER,
        ]));

        $this->assertInstanceOf(DialogRemovedUpdate::class, $update);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('dialog_removed', $update->toArray()['update_type']);
    }

    #[Test]
    public function it_rejects_an_unknown_update_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Unsupported update_type "unknown".');

        Update::fromArray(['update_type' => 'unknown', 'timestamp' => 1000]);
    }

    #[Test]
    public function it_requires_update_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "update_type" must be a string.');

        Update::fromArray(['timestamp' => 1000]);
    }

    #[Test]
    public function it_rejects_an_unknown_admin_permission(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Unsupported admin permission "fly".');

        Update::fromArray($this->payload('bot_admin_permissions_changed', [
            'chat_id' => 5,
            'user_id' => 7,
            'bot_id' => 42,
            'is_channel' => false,
            'is_admin' => false,
            'permissions' => ['fly'],
        ]));
    }

    #[Test]
    public function it_rejects_a_non_string_admin_permission(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Admin permission must be a string.');

        Update::fromArray($this->payload('bot_admin_permissions_changed', [
            'chat_id' => 5,
            'user_id' => 7,
            'bot_id' => 42,
            'is_channel' => false,
            'is_admin' => false,
            'permissions' => [7],
        ]));
    }
}
