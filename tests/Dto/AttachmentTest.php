<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\BotCommand;
use GeekCo\MaxPhpClient\Dto\ContactAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButton;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButtonRow;
use GeekCo\MaxPhpClient\Dto\LinkedMessage;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Enum\ButtonType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    #[Test]
    public function it_reads_a_location_attachment_with_top_level_coordinates(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'location',
            'latitude' => 55.751244,
            'longitude' => 37.618423,
        ]);

        $this->assertSame(AttachmentType::Location, $attachment->type);
        $this->assertSame(55.751244, $attachment->latitude);
        $this->assertSame(37.618423, $attachment->longitude);
        $this->assertSame(
            ['type' => 'location', 'latitude' => 55.751244, 'longitude' => 37.618423],
            $attachment->toArray(),
        );
    }

    #[Test]
    public function it_reads_a_location_attachment_from_a_nested_payload(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'location',
            'payload' => ['latitude' => 1.5, 'longitude' => 2.5],
        ]);

        $this->assertSame(1.5, $attachment->latitude);
        $this->assertSame(2.5, $attachment->longitude);
    }

    #[Test]
    public function it_prefers_top_level_coordinates_over_a_nested_payload(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'location',
            'latitude' => 10.5,
            'longitude' => 20.5,
            'payload' => ['latitude' => 1.5, 'longitude' => 2.5],
        ]);

        $this->assertSame(10.5, $attachment->latitude);
        $this->assertSame(20.5, $attachment->longitude);
    }

    #[Test]
    public function it_rejects_non_numeric_coordinates(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "latitude" must be a number.');

        Attachment::fromArray(['type' => 'location', 'latitude' => 'north']);
    }

    #[Test]
    public function it_leaves_coordinates_empty_for_other_attachment_types(): void
    {
        $attachment = Attachment::fromArray(['type' => 'image', 'payload' => ['url' => 'https://u', 'token' => 't']]);

        $this->assertNull($attachment->latitude);
        $this->assertNull($attachment->longitude);
        $this->assertSame(['type' => 'image', 'payload' => ['url' => 'https://u', 'token' => 't']], $attachment->toArray());
    }

    #[Test]
    public function it_reads_a_photo_id_of_an_image_attachment(): void
    {
        $attachment = Attachment::fromArray([
            'type' => 'image',
            'payload' => ['url' => 'https://u', 'token' => 't', 'photo_id' => 42],
        ]);

        $this->assertSame(42, $attachment->payload?->photoId);
    }

    #[Test]
    public function it_builds_a_sticker_attachment_request(): void
    {
        $request = AttachmentRequest::create(AttachmentType::Sticker, code: 'sticker-code');

        $this->assertSame('sticker-code', $request->code);
        $this->assertSame(['type' => 'sticker', 'payload' => ['code' => 'sticker-code']], $request->toArray());
    }

    #[Test]
    public function it_builds_a_location_attachment_request(): void
    {
        $request = AttachmentRequest::create(AttachmentType::Location, latitude: 55.751244, longitude: 37.618423);

        $this->assertSame(
            ['type' => 'location', 'latitude' => 55.751244, 'longitude' => 37.618423],
            $request->toArray(),
        );
    }

    #[Test]
    public function it_reads_a_sticker_and_location_attachment_request(): void
    {
        $sticker = AttachmentRequest::fromArray(['type' => 'sticker', 'payload' => ['code' => 'c1']]);
        $this->assertSame('c1', $sticker->code);
        $this->assertNull($sticker->rows);

        $location = AttachmentRequest::fromArray(['type' => 'location', 'latitude' => 1, 'longitude' => 2]);
        $this->assertSame(1.0, $location->latitude);
        $this->assertSame(2.0, $location->longitude);
        $this->assertArrayNotHasKey('payload', $location->toArray());
    }

    #[Test]
    public function it_does_not_emit_an_empty_payload(): void
    {
        $this->assertSame(['type' => 'contact'], AttachmentRequest::create(AttachmentType::Contact)->toArray());
    }

    #[Test]
    public function it_keeps_rows_and_code_out_of_the_payload_when_unset(): void
    {
        $row = new InlineKeyboardButtonRow([new InlineKeyboardButton(ButtonType::Callback, 'Go', payload: 'p')]);
        $request = new AttachmentRequest(AttachmentType::InlineKeyboard, rows: [$row]);

        $this->assertNull($request->code);
        $this->assertNull($request->latitude);
        $this->assertNull($request->longitude);
        $this->assertArrayNotHasKey('code', $request->toArray()['payload']);
    }

    #[Test]
    public function it_reads_a_contact_attachment_without_a_hash(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "hash" must be a string.');

        ContactAttachmentPayload::fromArray(['user_id' => 1]);
    }

    #[Test]
    public function it_reads_a_recipient_post_id(): void
    {
        $recipient = Recipient::fromArray(['chat_id' => 5, 'chat_type' => 'channel', 'post_id' => 'mid_post']);

        $this->assertSame('mid_post', $recipient->postId);
        $this->assertSame(
            ['chat_id' => 5, 'chat_type' => 'channel', 'post_id' => 'mid_post'],
            $recipient->toArray(),
        );
    }

    #[Test]
    public function it_keeps_a_recipient_without_a_post_id(): void
    {
        $this->assertNull(Recipient::fromArray(['chat_id' => 5])->postId);
        $this->assertSame(['chat_id' => 5], Recipient::fromArray(['chat_id' => 5])->toArray());
    }

    #[Test]
    public function it_reads_a_linked_message_from_the_specification_shape(): void
    {
        $linked = LinkedMessage::fromArray([
            'type' => 'reply',
            'message' => ['mid' => 'mid.1', 'seq' => 3, 'text' => 'Original'],
            'sender' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
            'chat_id' => 5,
        ]);

        $this->assertSame('reply', $linked->type);
        $this->assertSame('mid.1', $linked->mid);
        $this->assertSame(3, $linked->message?->seq);
        $this->assertSame(7, $linked->senderUser?->userId);
        $this->assertSame(7, $linked->sender);
        $this->assertSame(5, $linked->chatId);
        $this->assertSame('5', $linked->chat);
        $this->assertSame(['mid' => 'mid.1', 'seq' => 3, 'text' => 'Original'], $linked->toArray()['message']);
    }

    #[Test]
    public function it_reads_a_linked_message_with_a_sender_object_without_a_username(): void
    {
        $linked = LinkedMessage::fromArray([
            'type' => 'forward',
            'message' => ['mid' => 'mid.2', 'seq' => 1],
            'sender' => ['user_id' => 8, 'first_name' => 'Bob', 'is_bot' => true, 'last_activity_time' => 1000],
        ]);

        $this->assertSame(8, $linked->sender);
        $this->assertNull($linked->chatId);
        $this->assertNull($linked->chat);
    }

    #[Test]
    public function it_rejects_a_linked_message_without_a_mid(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "mid" must be a string.');

        LinkedMessage::fromArray(['type' => 'reply', 'sender' => 7]);
    }

    #[Test]
    public function it_allows_a_bot_command_without_a_description(): void
    {
        $command = new BotCommand('start');

        $this->assertNull($command->description);
        $this->assertSame(['name' => 'start'], $command->toArray());
        $this->assertNull(BotCommand::fromArray(['name' => 'start'])->description);
        $this->assertSame('start', $command->name);
    }
}
