<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Enum\UpdateType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UpdateTest extends TestCase
{
    #[Test]
    public function it_parses_a_message_created_without_top_level_user_and_chat_id(): void
    {
        $update = Update::fromArray([
            'timestamp' => 1786627401496,
            'message' => [
                'recipient' => ['chat_id' => 117541872, 'chat_type' => 'dialog', 'user_id' => 394979746],
                'timestamp' => 1786627401496,
                'body' => [
                    'mid' => 'mid.0000000007018bf0019ffb4aa7186a52',
                    'seq' => 117088413384469074,
                    'text' => '/start',
                ],
                'sender' => [
                    'user_id' => 277570130,
                    'first_name' => 'Евгений',
                    'last_name' => 'С',
                    'is_bot' => false,
                    'last_activity_time' => 1786627402000,
                    'name' => 'Евгений С',
                ],
            ],
            'user_locale' => 'ru',
            'update_type' => 'message_created',
        ]);

        $this->assertSame(UpdateType::MessageCreated, $update->updateType);
        $this->assertSame(277570130, $update->user?->userId);
        $this->assertSame('Евгений', $update->user?->firstName);
        $this->assertSame(117541872, $update->chatId);
        $this->assertSame('/start', $update->message?->body?->text);
        $this->assertSame('ru', $update->userLocale);
        $this->assertSame(1786627401496, $update->timestamp);
        $this->assertSame(117088413384469074, $update->message?->body?->seq);
        $this->assertIsInt($update->message?->body?->seq);
    }

    #[Test]
    public function it_uses_top_level_user_and_chat_id_when_present(): void
    {
        $update = Update::fromArray([
            'update_type' => 'bot_started',
            'timestamp' => 1000,
            'chat_id' => 5,
            'user' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
            'user_locale' => 'ru',
            'payload' => 'deep-link-data',
        ]);

        $this->assertSame(7, $update->user?->userId);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('ru', $update->userLocale);
        $this->assertSame('deep-link-data', $update->payload);
    }

    #[Test]
    public function it_parses_a_callback_user_and_chat_from_callback_message(): void
    {
        $update = Update::fromArray([
            'update_type' => 'message_callback',
            'timestamp' => 1000,
            'callback' => [
                'callback_id' => 'c1',
                'payload' => 'button-payload',
                'message' => [
                    'sender' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
                    'recipient' => ['chat_id' => 5],
                    'timestamp' => 1000,
                    'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'press'],
                ],
            ],
        ]);

        $this->assertSame(7, $update->user?->userId);
        $this->assertSame(5, $update->chatId);
        $this->assertSame('button-payload', $update->callback?->payload);
    }

    #[Test]
    public function it_roundtrips_an_update_with_new_fields(): void
    {
        $payload = [
            'update_type' => 'message_removed',
            'timestamp' => 1000,
            'chat_id' => 5,
            'user_id' => 7,
            'message_id' => 'm1',
            'inviter_id' => 3,
            'admin_id' => 4,
            'title' => 'New title',
            'muted_until' => 2000,
            'user_locale' => 'ru',
        ];

        $decoded = Update::fromArray(Update::fromArray($payload)->toArray());

        $this->assertSame(5, $decoded->chatId);
        $this->assertSame(7, $decoded->userId);
        $this->assertSame('m1', $decoded->messageId);
        $this->assertSame(3, $decoded->inviterId);
        $this->assertSame(4, $decoded->adminId);
        $this->assertSame('New title', $decoded->title);
        $this->assertSame(2000, $decoded->mutedUntil);
        $this->assertSame('ru', $decoded->userLocale);
    }

    #[Test]
    public function it_parses_a_message_removed_without_a_user_object(): void
    {
        $update = Update::fromArray([
            'update_type' => 'message_removed',
            'timestamp' => 1000,
            'chat_id' => 5,
            'user_id' => 7,
            'message_id' => 'm1',
        ]);

        $this->assertNull($update->user);
        $this->assertSame(5, $update->chatId);
        $this->assertSame(7, $update->userId);
        $this->assertSame('m1', $update->messageId);
    }

    #[Test]
    public function it_parses_a_callback_with_a_deleted_message(): void
    {
        $update = Update::fromArray([
            'update_type' => 'message_callback',
            'timestamp' => 1000,
            'callback' => ['callback_id' => 'c1'],
        ]);

        $this->assertNull($update->user);
        $this->assertNull($update->chatId);
        $this->assertSame('c1', $update->callback?->callbackId);
    }

    #[Test]
    public function it_keeps_the_invalid_payload_error_for_broken_structures(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "update_type" must be a string.');

        Update::fromArray(['timestamp' => 1000]);
    }
}
