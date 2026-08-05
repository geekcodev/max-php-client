<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\Chat;
use GeekCo\MaxPhpClient\Dto\ImageAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Enum\ChatStatus;
use GeekCo\MaxPhpClient\Enum\ChatType;
use GeekCo\MaxPhpClient\Enum\UpdateType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DtoTest extends TestCase
{
    #[Test]
    public function it_roundtrips_a_chat(): void
    {
        $chat = Chat::fromArray([
            'chat_id' => 1,
            'type' => 'channel',
            'status' => 'active',
            'last_event_time' => 100,
            'participants_count' => 3,
            'is_public' => true,
            'title' => 'Channel',
        ]);

        $this->assertSame(ChatType::Channel, $chat->type);
        $this->assertSame(ChatStatus::Active, $chat->status);
        $this->assertSame('Channel', $chat->title);

        $decoded = Chat::fromArray($chat->toArray());
        $this->assertSame($chat->chatId, $decoded->chatId);
        $this->assertSame($chat->isPublic, $decoded->isPublic);
    }

    #[Test]
    public function it_roundtrips_an_update_with_message(): void
    {
        $update = Update::fromArray([
            'update_type' => 'message_created',
            'timestamp' => 1000,
            'chat_id' => 5,
            'user' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
            'message' => [
                'sender' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
                'recipient' => ['chat_id' => 5],
                'timestamp' => 1000,
                'body' => [
                    'mid' => 'm1',
                    'seq' => 1,
                    'text' => 'Hi',
                    'attachments' => [
                        ['type' => 'image', 'payload' => ['url' => 'https://iu.oneme.ru/a.jpg', 'token' => 't1']],
                    ],
                ],
            ],
        ]);

        $this->assertSame(UpdateType::MessageCreated, $update->updateType);
        $this->assertSame('Alice', $update->user->firstName);
        $this->assertSame('m1', $update->message?->body?->mid);

        $attachment = $update->message?->body?->attachments[0];
        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertSame(AttachmentType::Image, $attachment->type);
        $this->assertInstanceOf(ImageAttachmentPayload::class, $attachment->payload);
        $this->assertSame('https://iu.oneme.ru/a.jpg', $attachment->payload->url);
    }

    #[Test]
    public function it_parses_an_update_without_chat_id(): void
    {
        $update = Update::fromArray([
            'update_type' => 'message_callback',
            'timestamp' => 1000,
            'user' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
        ]);

        $this->assertSame(UpdateType::MessageCallback, $update->updateType);
        $this->assertNull($update->chatId);
    }

    #[Test]
    public function it_roundtrips_a_message(): void
    {
        $message = Message::fromArray([
            'sender' => null,
            'recipient' => ['chat_id' => 1],
            'timestamp' => 1,
            'body' => ['mid' => 'x', 'seq' => 2, 'text' => 'hello'],
            'stat' => ['views' => 10],
        ]);

        $this->assertSame(2, $message->body?->seq);
        $this->assertSame(10, $message->stat?->views);

        $decoded = Message::fromArray($message->toArray());
        $this->assertSame('hello', $decoded->body?->text);
        $this->assertSame(1, $decoded->recipient->chatId);
    }
}
