<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Dto\Chat;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Enum\ChatStatus;
use GeekCo\MaxPhpClient\Enum\ChatType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
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
    public function it_parses_participants_as_an_activity_map(): void
    {
        $chat = Chat::fromArray([
            'chat_id' => -79032376695376,
            'type' => 'chat',
            'status' => 'active',
            'last_event_time' => 1791451784417,
            'participants_count' => 5,
            'is_public' => false,
            'title' => 'Team',
            'participants' => ['46493418' => 1791451784417, '394979746' => 0],
        ]);

        $this->assertSame([46493418 => 1791451784417, 394979746 => 0], $chat->participants);
        $this->assertSame(-79032376695376, $chat->chatId);
        $this->assertSame('Team', $chat->title);
    }

    #[Test]
    public function it_rejects_a_participants_list(): void
    {
        $this->expectException(InvalidResponseException::class);

        Chat::fromArray([
            'chat_id' => 1,
            'type' => 'chat',
            'status' => 'active',
            'last_event_time' => 1,
            'participants_count' => 1,
            'is_public' => false,
            'participants' => [['user_id' => 7, 'first_name' => 'Ivan', 'is_bot' => false]],
        ]);
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
