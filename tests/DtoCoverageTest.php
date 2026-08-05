<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Dto\AddChatMembersResult;
use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AudioAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\BotCommand;
use GeekCo\MaxPhpClient\Dto\BotInfo;
use GeekCo\MaxPhpClient\Dto\Callback;
use GeekCo\MaxPhpClient\Dto\Chat;
use GeekCo\MaxPhpClient\Dto\ChatAdmin;
use GeekCo\MaxPhpClient\Dto\ChatAdminsResult;
use GeekCo\MaxPhpClient\Dto\ChatListResult;
use GeekCo\MaxPhpClient\Dto\ChatMember;
use GeekCo\MaxPhpClient\Dto\ChatMembersResult;
use GeekCo\MaxPhpClient\Dto\EditChatBody;
use GeekCo\MaxPhpClient\Dto\ErrorResponse;
use GeekCo\MaxPhpClient\Dto\FailedUserDetails;
use GeekCo\MaxPhpClient\Dto\FileAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\Image;
use GeekCo\MaxPhpClient\Dto\ImageAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButton;
use GeekCo\MaxPhpClient\Dto\InlineKeyboardButtonRow;
use GeekCo\MaxPhpClient\Dto\LinkedMessage;
use GeekCo\MaxPhpClient\Dto\LocationAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\MessageStat;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\NewMessageLink;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\PinMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\Subscription;
use GeekCo\MaxPhpClient\Dto\SuccessResponse;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\UploadResult;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Dto\UserWithPhoto;
use GeekCo\MaxPhpClient\Dto\VideoAttachmentPayload;
use GeekCo\MaxPhpClient\Dto\VideoInfo;
use GeekCo\MaxPhpClient\Dto\VideoUrls;
use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Enum\ButtonType;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Enum\ChatStatus;
use GeekCo\MaxPhpClient\Enum\ChatType;
use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DtoCoverageTest extends TestCase
{
    private const USER_ARRAY = [
        'user_id' => 1,
        'first_name' => 'Bot',
        'last_name' => 'Smith',
        'username' => 'bot',
        'is_bot' => true,
        'last_activity_time' => 100,
        'name' => 'Old Name',
    ];

    private const MESSAGE_ARRAY = [
        'sender' => null,
        'recipient' => ['chat_id' => 5],
        'timestamp' => 10,
        'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hi', 'format' => 'markdown'],
        'stat' => ['views' => 3],
        'url' => 'https://max.ru/msg',
    ];

    private function roundtrip(array $array, string $dto): mixed
    {
        return $dto::fromArray($dto::fromArray($array)->toArray());
    }

    #[Test]
    public function it_roundtrips_a_user(): void
    {
        $user = $this->roundtrip(self::USER_ARRAY, User::class);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('bot', $user->username);
        $this->assertSame('Old Name', $user->name);
    }

    #[Test]
    public function it_roundtrips_a_user_with_photo(): void
    {
        $array = [...self::USER_ARRAY, 'description' => 'desc', 'avatar_url' => 'https://x/a.jpg', 'full_avatar_url' => 'https://x/f.jpg'];
        $user = $this->roundtrip($array, UserWithPhoto::class);

        $this->assertInstanceOf(UserWithPhoto::class, $user);
        $this->assertSame('desc', $user->description);
        $this->assertSame('https://x/a.jpg', $user->avatarUrl);
    }

    #[Test]
    public function it_roundtrips_bot_info(): void
    {
        $array = [...self::USER_ARRAY, 'commands' => [['name' => 'start', 'description' => 'Start']]];
        $info = $this->roundtrip($array, BotInfo::class);

        $this->assertInstanceOf(BotInfo::class, $info);
        $this->assertSame('start', $info->commands[0]->name);
    }

    #[Test]
    public function it_roundtrips_a_video_urls(): void
    {
        $urls = VideoUrls::fromArray(['mp4' => 'https://v/m.mp4', 'preview' => 'https://v/p.jpg'])->toArray();

        $this->assertSame(['mp4' => 'https://v/m.mp4', 'preview' => 'https://v/p.jpg'], $urls);
        $this->assertNull(VideoUrls::fromArray([])->mp4);
    }

    #[Test]
    public function it_roundtrips_video_info(): void
    {
        $array = ['video_token' => 'v1', 'file_name' => 'c.mp4', 'duration' => 5, 'size' => 100, 'url' => ['mp4' => 'https://v/m.mp4']];
        $info = $this->roundtrip($array, VideoInfo::class);

        $this->assertInstanceOf(VideoInfo::class, $info);
        $this->assertSame('https://v/m.mp4', $info->url->mp4);
    }

    #[Test]
    public function it_roundtrips_a_chat_with_pinned_message_and_participants(): void
    {
        $array = [
            'chat_id' => 1,
            'type' => 'chat',
            'status' => 'active',
            'last_event_time' => 10,
            'participants_count' => 2,
            'is_public' => true,
            'title' => 'T',
            'icon' => 'https://i.jpg',
            'owner_id' => 1,
            'participants' => [self::USER_ARRAY],
            'link' => 'https://max.ru/c/1',
            'description' => 'd',
            'dialog_with_user' => self::USER_ARRAY,
            'messages_count' => 5,
            'pinned_message' => self::MESSAGE_ARRAY,
        ];
        $chat = $this->roundtrip($array, Chat::class);

        $this->assertInstanceOf(Chat::class, $chat);
        $this->assertSame(ChatType::Chat, $chat->type);
        $this->assertSame(ChatStatus::Active, $chat->status);
        $this->assertSame('d', $chat->description);
        $this->assertSame(5, $chat->messagesCount);
        $this->assertSame('T', $chat->title);
        $this->assertSame('hi', $chat->pinnedMessage?->body?->text);
    }

    #[Test]
    public function it_roundtrips_a_chat_member(): void
    {
        $array = [...self::USER_ARRAY, 'last_access_time' => 5, 'is_owner' => true, 'is_admin' => false, 'join_time' => 2, 'permissions' => ['write'], 'alias' => 'A'];
        $member = $this->roundtrip($array, ChatMember::class);

        $this->assertInstanceOf(ChatMember::class, $member);
        $this->assertTrue($member->isOwner);
        $this->assertSame([ChatAdminPermission::Write], $member->permissions);
        $this->assertSame('A', $member->alias);
    }

    #[Test]
    public function it_roundtrips_a_chat_admin(): void
    {
        $admin = $this->roundtrip(['user_id' => 1, 'permissions' => ['write', 'pin_message'], 'alias' => 'M'], ChatAdmin::class);

        $this->assertInstanceOf(ChatAdmin::class, $admin);
        $this->assertSame('M', $admin->alias);
        $this->assertCount(2, $admin->permissions);
    }

    #[Test]
    public function it_roundtrips_a_message_with_link_and_attachments(): void
    {
        $array = [
            'sender' => self::USER_ARRAY,
            'recipient' => ['chat_id' => 5],
            'timestamp' => 10,
            'link' => ['type' => 'forward', 'sender' => 2, 'mid' => 'm0', 'chat' => 'chat'],
            'body' => [
                'mid' => 'm1',
                'seq' => 1,
                'text' => 'hi',
                'caption' => 'cap',
                'format' => 'html',
                'attachments' => [
                    ['type' => 'image', 'payload' => ['url' => 'https://u/a.jpg', 'token' => 't']],
                    ['type' => 'video', 'payload' => ['url' => ['mp4' => 'https://v/m.mp4'], 'token' => 'tv', 'duration' => 3]],
                    ['type' => 'audio', 'payload' => ['url' => 'https://a.mp3', 'token' => 'ta', 'duration' => 60]],
                    ['type' => 'file', 'payload' => ['url' => 'https://f', 'token' => 'tf', 'file_size' => 10]],
                    ['type' => 'location', 'payload' => ['latitude' => 1.5, 'longitude' => 2.5]],
                    ['type' => 'sticker', 'payload' => null],
                    ['type' => 'inline_keyboard', 'payload' => ['rows' => [['buttons' => [['type' => 'callback', 'text' => 'Go', 'payload' => 'p']]]]]],
                ],
            ],
            'stat' => ['views' => 3],
            'url' => 'https://max.ru/msg',
        ];
        $message = $this->roundtrip($array, Message::class);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame('cap', $message->body?->caption);
        $this->assertSame(TextFormat::Html, $message->body?->format);
        $this->assertCount(7, $message->body?->attachments ?? []);
        $this->assertInstanceOf(ImageAttachmentPayload::class, $message->body?->attachments[0]->payload);
        $this->assertInstanceOf(VideoAttachmentPayload::class, $message->body?->attachments[1]->payload);
        $this->assertInstanceOf(InlineKeyboardAttachmentPayload::class, $message->body?->attachments[6]->payload);
        $this->assertSame('forward', $message->link?->type);
    }

    #[Test]
    public function it_roundtrips_attachment_payloads(): void
    {
        $audio = AudioAttachmentPayload::fromArray(['url' => 'u', 'token' => 't', 'duration' => 1]);
        $file = FileAttachmentPayload::fromArray(['url' => 'u', 'token' => 't', 'file_size' => 1]);
        $image = ImageAttachmentPayload::fromArray(['url' => 'u', 'token' => 't']);
        $photo = PhotoAttachmentPayload::fromArray(['url' => 'u', 'width' => 1, 'height' => 2]);
        $location = LocationAttachmentPayload::fromArray(['latitude' => 1.5, 'longitude' => 2.5]);

        $this->assertSame(1, $audio->duration);
        $this->assertSame(1, $file->fileSize);
        $this->assertSame('u', $image->url);
        $this->assertSame(2, $photo->height);
        $this->assertSame(2.5, $location->longitude);

        $this->assertSame(['latitude' => 1.5, 'longitude' => 2.5], $location->toArray());
        $this->assertNull(ImageAttachmentPayload::fromArray([])->token);
    }

    #[Test]
    public function it_roundtrips_an_inline_keyboard(): void
    {
        $button = new InlineKeyboardButton(ButtonType::Link, 'Go', url: 'https://x');
        $row = new InlineKeyboardButtonRow([$button]);
        $payload = new InlineKeyboardAttachmentPayload([$row]);

        $this->assertSame(ButtonType::Link, $button->type);
        $this->assertSame(['rows' => [['buttons' => [['type' => 'link', 'text' => 'Go', 'url' => 'https://x']]]]], $payload->toArray());
        $this->assertSame(['buttons' => [['type' => 'link', 'text' => 'Go', 'url' => 'https://x']]], $row->toArray());
        $this->assertSame('https://x', $button->toArray()['url']);
    }

    #[Test]
    public function it_roundtrips_an_attachment_request_with_rows(): void
    {
        $row = new InlineKeyboardButtonRow([new InlineKeyboardButton(ButtonType::Callback, 'Go', payload: 'p')]);
        $request = new AttachmentRequest(AttachmentType::InlineKeyboard, rows: [$row]);

        $this->assertSame('inline_keyboard', $request->toArray()['type']);
        $this->assertSame([['buttons' => [['type' => 'callback', 'text' => 'Go', 'payload' => 'p']]]], $request->toArray()['payload']['rows']);
    }

    #[Test]
    public function it_roundtrips_an_attachment_request_with_a_token(): void
    {
        $request = new AttachmentRequest(AttachmentType::Image, token: 't1');

        $this->assertSame(['type' => 'image', 'payload' => ['token' => 't1']], $request->toArray());

        $created = AttachmentRequest::create(AttachmentType::Image, token: 't2');
        $this->assertSame(['type' => 'image', 'payload' => ['token' => 't2']], $created->toArray());

        $parsed = AttachmentRequest::fromArray(['type' => 'image', 'payload' => ['token' => 't3', 'url' => 'https://u']]);
        $this->assertSame('t3', $parsed->token);
        $this->assertSame('https://u', $parsed->url);

        $noPayload = AttachmentRequest::fromArray(['type' => 'image']);
        $this->assertNull($noPayload->token);
        $this->assertNull($noPayload->url);
        $this->assertNull($noPayload->rows);
    }

    #[Test]
    public function it_roundtrips_a_pin_message_body(): void
    {
        $body = PinMessageBody::create('m1', notify: true);

        $this->assertSame(['message_id' => 'm1', 'notify' => true], $body->toArray());
        $this->assertSame('m1', PinMessageBody::fromArray(['message_id' => 'm1', 'notify' => false])->messageId);
    }

    #[Test]
    public function it_roundtrips_a_photo_payload(): void
    {
        $photo = PhotoAttachmentPayload::fromArray(['url' => 'https://p', 'width' => 100, 'height' => 50]);

        $this->assertSame(100, $photo->width);
        $this->assertSame(['url' => 'https://p', 'width' => 100, 'height' => 50], $photo->toArray());
        $this->assertNull(PhotoAttachmentPayload::fromArray([])->url);
    }

    #[Test]
    public function it_constructs_an_attachment_directly(): void
    {
        $attachment = new Attachment(AttachmentType::Image, new ImageAttachmentPayload(url: 'https://u'));

        $this->assertSame('image', $attachment->toArray()['type']);
        $this->assertSame('https://u', $attachment->toArray()['payload']['url']);

        $raw = new Attachment(AttachmentType::Sticker, ['raw' => 'data']);
        $this->assertSame(['raw' => 'data'], $raw->payload);

        $share = Attachment::fromArray(['type' => 'share', 'payload' => ['url' => 'https://s']]);
        $this->assertSame(['url' => 'https://s'], $share->payload);
    }

    #[Test]
    public function it_rejects_an_invalid_admin_permission(): void
    {
        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        ChatAdmin::fromArray(['user_id' => 1, 'permissions' => ['not-a-permission']]);
    }

    #[Test]
    public function it_rejects_a_non_string_admin_permission(): void
    {
        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        ChatAdmin::fromArray(['user_id' => 1, 'permissions' => [5]]);
    }

    #[Test]
    public function it_rejects_an_invalid_member_permission(): void
    {
        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        ChatMember::fromArray([...self::USER_ARRAY, 'last_access_time' => 1, 'is_owner' => false, 'is_admin' => false, 'join_time' => 1, 'permissions' => ['nope']]);
    }

    #[Test]
    public function it_roundtrips_new_message_body_with_attachments(): void
    {
        $body = new NewMessageBody(
            text: 'hi',
            attachments: [new AttachmentRequest(AttachmentType::Image, token: 't')],
            link: new NewMessageLink('reply', 'https://x', 'tk'),
            notify: true,
            format: TextFormat::Html,
        );

        $this->assertSame('hi', $body->toArray()['text']);
        $this->assertSame('reply', $body->toArray()['link']['type']);
        $this->assertSame('html', $body->toArray()['format']);
        $this->assertSame([['type' => 'image', 'payload' => ['token' => 't']]], $body->toArray()['attachments']);
    }

    #[Test]
    public function it_roundtrips_an_image(): void
    {
        $this->assertSame(['url' => 'https://i.jpg'], (new Image('https://i.jpg'))->toArray());
    }

    #[Test]
    public function it_roundtrips_a_message_stat(): void
    {
        $this->assertSame(3, MessageStat::fromArray(['views' => 3])->views);
        $this->assertSame(['views' => 3], (new MessageStat(3))->toArray());
    }

    #[Test]
    public function it_roundtrips_a_linked_message(): void
    {
        $linked = $this->roundtrip(['type' => 'forward', 'sender' => 2, 'mid' => 'm', 'chat' => 'c'], LinkedMessage::class);

        $this->assertInstanceOf(LinkedMessage::class, $linked);
        $this->assertSame('c', $linked->chat);
    }

    #[Test]
    public function it_roundtrips_an_error_response(): void
    {
        $error = $this->roundtrip(['code' => 'bad', 'message' => 'm', 'error' => 'detail'], ErrorResponse::class);

        $this->assertInstanceOf(ErrorResponse::class, $error);
        $this->assertSame('detail', $error->error);
    }

    #[Test]
    public function it_roundtrips_a_subscription(): void
    {
        $sub = $this->roundtrip(['url' => 'https://x', 'update_types' => ['message_created']], Subscription::class);

        $this->assertInstanceOf(Subscription::class, $sub);
        $this->assertSame(['message_created'], $sub->updateTypes);
    }

    #[Test]
    public function it_roundtrips_an_upload_result(): void
    {
        $result = UploadResult::fromArray(['url' => 'https://u', 'token' => 't'])->toArray();

        $this->assertSame(['url' => 'https://u', 'token' => 't'], $result);
        $this->assertNull(UploadResult::fromArray(['url' => 'https://u'])->token);
    }

    #[Test]
    public function it_roundtrips_an_add_chat_members_result(): void
    {
        $result = $this->roundtrip([
            'success' => true,
            'failed_user_ids' => [5],
            'details' => [['user_id' => 5, 'reason' => 'blocked']],
        ], AddChatMembersResult::class);

        $this->assertInstanceOf(AddChatMembersResult::class, $result);
        $this->assertSame([5], $result->failedUserIds);
        $this->assertSame('blocked', $result->details[0]->reason);
    }

    #[Test]
    public function it_roundtrips_a_failed_user_details(): void
    {
        $this->assertSame(['user_id' => 5, 'reason' => 'blocked'], (new FailedUserDetails(5, 'blocked'))->toArray());
    }

    #[Test]
    public function it_roundtrips_list_result_wrappers(): void
    {
        $chat = ['chat_id' => 1, 'type' => 'chat', 'status' => 'active', 'last_event_time' => 1, 'participants_count' => 1, 'is_public' => false];
        $member = [...self::USER_ARRAY, 'last_access_time' => 1, 'is_owner' => false, 'is_admin' => false, 'join_time' => 1];
        $admin = ['user_id' => 1, 'permissions' => ['write']];

        $chats = ChatListResult::fromArray(['chats' => [$chat], 'marker' => 1]);
        $members = ChatMembersResult::fromArray(['members' => [$member], 'marker' => 2]);
        $admins = ChatAdminsResult::fromArray(['admins' => [$admin], 'marker' => 3]);

        $this->assertSame(1, ChatListResult::fromArray($chats->toArray())->marker);
        $this->assertSame(2, ChatMembersResult::fromArray($members->toArray())->marker);
        $this->assertSame(3, ChatAdminsResult::fromArray($admins->toArray())->marker);
    }

    #[Test]
    public function it_roundtrips_request_bodies(): void
    {
        $recipient = new Recipient(chatId: 5);
        $this->assertSame(['chat_id' => 5], $recipient->toArray());
        $this->assertNull((new Recipient())->chatId);

        $pin = new PinMessageBody('m1', notify: true);
        $this->assertSame(['message_id' => 'm1', 'notify' => true], $pin->toArray());

        $edit = new EditChatBody(title: 'T', icon: 'I', pin: true, notify: true);
        $this->assertSame(['title' => 'T', 'icon' => 'I', 'pin' => true, 'notify' => true], $edit->toArray());

        $success = new SuccessResponse(true, 'done');
        $this->assertSame(['success' => true, 'message' => 'done'], $success->toArray());

        $command = new BotCommand('start', 'desc');
        $this->assertSame(['name' => 'start', 'description' => 'desc'], $command->toArray());

        $link = new NewMessageLink('reply', 'https://x');
        $this->assertSame(['type' => 'reply', 'url' => 'https://x'], $link->toArray());

        $callback = new Callback('c1', 'payload', Message::fromArray(self::MESSAGE_ARRAY));
        $this->assertSame('c1', $callback->callbackId);
        $this->assertSame('payload', $callback->toArray()['payload']);
        $this->assertSame('hi', $callback->toArray()['message']['body']['text']);
    }

    #[Test]
    public function it_roundtrips_an_update_with_callback(): void
    {
        $array = [
            'update_type' => 'message_callback',
            'timestamp' => 10,
            'chat_id' => 5,
            'is_channel' => true,
            'user' => self::USER_ARRAY,
            'callback' => ['callback_id' => 'c1', 'payload' => 'p', 'message' => self::MESSAGE_ARRAY],
        ];
        $update = $this->roundtrip($array, Update::class);

        $this->assertInstanceOf(Update::class, $update);
        $this->assertTrue($update->isChannel);
        $this->assertSame('p', $update->callback?->payload);
    }

    #[Test]
    public function it_roundtrips_a_new_message_body_without_attachments(): void
    {
        $body = NewMessageBody::fromArray(['text' => 'only text'])->toArray();

        $this->assertSame(['text' => 'only text'], $body);
        $this->assertNull(NewMessageBody::fromArray([])->format);
    }

    #[Test]
    public function it_maps_upload_type_enum(): void
    {
        $this->assertSame(['file', 'image', 'video', 'audio'], array_map(
            static fn (UploadType $type): string => $type->value,
            [UploadType::File, UploadType::Image, UploadType::Video, UploadType::Audio],
        ));
    }

    #[Test]
    public function it_parses_a_new_message_link(): void
    {
        $link = NewMessageLink::fromArray(['type' => 'reply', 'url' => 'https://x', 'token' => 'tk']);

        $this->assertSame('reply', $link->type);
        $this->assertSame('https://x', $link->url);
        $this->assertSame('tk', $link->token);
    }

    #[Test]
    public function it_parses_an_edit_chat_body(): void
    {
        $body = EditChatBody::fromArray(['title' => 'T', 'icon' => 'I', 'pin' => true, 'notify' => false]);

        $this->assertSame('T', $body->title);
        $this->assertSame('I', $body->icon);
        $this->assertTrue($body->pin);
        $this->assertFalse($body->notify);
    }

    #[Test]
    public function it_parses_an_image(): void
    {
        $this->assertSame('https://i.jpg', Image::fromArray(['url' => 'https://i.jpg'])->url);
    }

    #[Test]
    public function it_rejects_a_message_without_an_object_recipient(): void
    {
        $this->expectException(InvalidResponseException::class);

        Message::fromArray(['recipient' => 'not-an-object']);
    }

    #[Test]
    public function it_rejects_video_info_without_an_object_url(): void
    {
        $this->expectException(InvalidResponseException::class);

        VideoInfo::fromArray(['url' => 'not-an-object']);
    }

    #[Test]
    public function it_rejects_an_attachment_without_a_type(): void
    {
        $this->expectException(InvalidResponseException::class);

        Attachment::fromArray(['payload' => ['url' => 'https://u']]);
    }
}
