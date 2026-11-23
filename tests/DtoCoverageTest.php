<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Enum\MessageLinkType;
use GeekCo\MaxPhpClient\Dto\ModifyMembersResult;
use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\BotCommand;
use GeekCo\MaxPhpClient\Dto\BotInfo;
use GeekCo\MaxPhpClient\Dto\Callback;
use GeekCo\MaxPhpClient\Dto\Chat;
use GeekCo\MaxPhpClient\Dto\ChatAdmin;
use GeekCo\MaxPhpClient\Dto\ChatAdminsResult;
use GeekCo\MaxPhpClient\Dto\ChatIcon;
use GeekCo\MaxPhpClient\Dto\ChatList;
use GeekCo\MaxPhpClient\Dto\ChatMember;
use GeekCo\MaxPhpClient\Dto\ChatMembersList;
use GeekCo\MaxPhpClient\Dto\BotCommandsInfo;
use GeekCo\MaxPhpClient\Dto\ChatPatch;
use GeekCo\MaxPhpClient\Dto\Error;
use GeekCo\MaxPhpClient\Dto\FailedUserDetails;
use GeekCo\MaxPhpClient\Dto\Image;
use GeekCo\MaxPhpClient\Dto\LinkedMessage;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\MessageStat;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\NewMessageLink;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\PhotoAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\PhotoAttachmentRequestPayload;
use GeekCo\MaxPhpClient\Dto\PinMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\Subscription;
use GeekCo\MaxPhpClient\Dto\SimpleQueryResult;
use GeekCo\MaxPhpClient\Dto\UpdateList;
use GeekCo\MaxPhpClient\Dto\UploadEndpoint;
use GeekCo\MaxPhpClient\Dto\UploadedInfo;
use GeekCo\MaxPhpClient\Dto\User;
use GeekCo\MaxPhpClient\Dto\UserWithPhoto;
use GeekCo\MaxPhpClient\Dto\VideoAttachmentDetails;
use GeekCo\MaxPhpClient\Dto\VideoUrls;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Enum\ChatStatus;
use GeekCo\MaxPhpClient\Enum\ChatType;
use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
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
        $urls = VideoUrls::fromArray(['mp4_720' => 'https://v/m.mp4', 'mp4_480' => 'https://v/p.jpg'])->toArray();

        $this->assertSame(['mp4_720' => 'https://v/m.mp4', 'mp4_480' => 'https://v/p.jpg'], $urls);
        $this->assertNull(VideoUrls::fromArray([])->mp4_720);
    }

    #[Test]
    public function it_roundtrips_video_info(): void
    {
        $array = [
            'token' => 'v1',
            'urls' => ['mp4_720' => 'https://v/m.mp4'],
            'thumbnail' => ['url' => 'https://v/t.jpg', 'width' => 320, 'height' => 180],
            'width' => 1920,
            'height' => 1080,
            'duration' => 5,
        ];
        $info = $this->roundtrip($array, VideoAttachmentDetails::class);

        $this->assertInstanceOf(VideoAttachmentDetails::class, $info);
        $this->assertSame('v1', $info->token);
        $this->assertSame('https://v/m.mp4', $info->urls?->mp4_720);
        $this->assertSame('https://v/t.jpg', $info->thumbnail?->url);
        $this->assertSame(1080, $info->height);
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
            'icon' => ['url' => 'https://i.jpg'],
            'owner_id' => 1,
            'participants' => [7 => 1000],
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
        $this->assertSame('https://i.jpg', $chat->icon?->url);
        $this->assertSame('hi', $chat->pinnedMessage?->body?->text);
        $this->assertSame([7 => 1000], $chat->participants);
        $this->assertInstanceOf(UserWithPhoto::class, $chat->dialogWithUser);
    }

    #[Test]
    public function it_parses_a_suspended_chat_status(): void
    {
        $chat = Chat::fromArray([
            'chat_id' => 1,
            'type' => 'chat',
            'status' => 'suspended',
            'last_event_time' => 1,
            'participants_count' => 1,
            'is_public' => false,
        ]);

        $this->assertSame(ChatStatus::Suspended, $chat->status);
        $this->assertSame('suspended', $chat->toArray()['status']);
    }

    #[Test]
    public function it_parses_dialog_with_user_as_user_with_photo(): void
    {
        $array = [
            'chat_id' => 1,
            'type' => 'dialog',
            'status' => 'active',
            'last_event_time' => 100,
            'participants_count' => 2,
            'is_public' => false,
            'dialog_with_user' => [
                'user_id' => 7,
                'first_name' => 'Ivan',
                'last_name' => 'Petrov',
                'username' => 'ivan',
                'is_bot' => false,
                'last_activity_time' => 1000,
                'description' => 'desc',
                'avatar_url' => 'https://iu.oneme.ru/av.jpg',
                'full_avatar_url' => 'https://iu.oneme.ru/av_full.jpg',
            ],
        ];
        $chat = Chat::fromArray($array);
        $partner = $chat->dialogWithUser;

        $this->assertInstanceOf(UserWithPhoto::class, $partner);
        $this->assertSame('https://iu.oneme.ru/av.jpg', $partner?->avatarUrl);
        $this->assertSame('https://iu.oneme.ru/av_full.jpg', $partner?->fullAvatarUrl);
        $this->assertSame('desc', $partner?->description);

        $decoded = Chat::fromArray($chat->toArray());
        $this->assertSame('https://iu.oneme.ru/av.jpg', $decoded->dialogWithUser?->avatarUrl);
        $this->assertSame('https://iu.oneme.ru/av_full.jpg', $decoded->dialogWithUser?->fullAvatarUrl);
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
    public function it_roundtrips_a_pin_message_body(): void
    {
        $body = PinMessageBody::create('m1', notify: true);

        $this->assertSame(['message_id' => 'm1', 'notify' => true], $body->toArray());
        $this->assertSame('m1', PinMessageBody::fromArray(['message_id' => 'm1', 'notify' => false])->messageId);
    }

    #[Test]
    public function it_rejects_an_invalid_admin_permission(): void
    {
        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        ChatAdmin::fromArray(['user_id' => 1, 'permissions' => ['not-a-permission']]);
    }

    #[Test]
    public function it_parses_deprecated_admin_permissions(): void
    {
        $permissions = ['post_edit_delete_message', 'edit_message', 'delete_message'];

        $admin = ChatAdmin::fromArray(['user_id' => 1, 'permissions' => $permissions]);
        $member = ChatMember::fromArray([
            ...self::USER_ARRAY,
            'last_access_time' => 1,
            'is_owner' => false,
            'is_admin' => true,
            'join_time' => 1,
            'permissions' => $permissions,
        ]);

        $this->assertSame(
            [ChatAdminPermission::PostEditDeleteMessage, ChatAdminPermission::EditMessage, ChatAdminPermission::DeleteMessage],
            $admin->permissions,
        );
        $this->assertSame($admin->permissions, $member->permissions);
        $this->assertTrue(ChatAdminPermission::EditMessage->isDeprecated());
        $this->assertFalse(ChatAdminPermission::Write->isDeprecated());
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
    public function it_parses_users_without_last_activity_time(): void
    {
        $array = ['user_id' => 1, 'first_name' => 'A', 'is_bot' => false];

        $this->assertNull(User::fromArray($array)->lastActivityTime);
        $this->assertNull(UserWithPhoto::fromArray($array)->lastActivityTime);
        $this->assertNull(BotInfo::fromArray($array)->lastActivityTime);
        $this->assertNull(ChatMember::fromArray([...$array, 'last_access_time' => 1, 'is_owner' => false, 'is_admin' => false, 'join_time' => 1])->lastActivityTime);
    }

    #[Test]
    public function it_roundtrips_new_message_body_with_attachments(): void
    {
        $body = new NewMessageBody(
            text: 'hi',
            attachments: [new PhotoAttachmentRequest(new PhotoAttachmentRequestPayload(token: 't'))],
            link: new NewMessageLink(MessageLinkType::Reply, 'm1'),
            notify: true,
            format: TextFormat::Html,
        );

        $this->assertSame('hi', $body->toArray()['text']);
        $this->assertSame('reply', $body->toArray()['link']['type']);
        $this->assertSame('m1', $body->toArray()['link']['mid']);
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
        $this->assertSame(2, $linked->senderId);
        $this->assertSame('c', $linked->chat);
    }

    #[Test]
    public function it_reads_a_linked_message_with_a_numeric_string_sender(): void
    {
        $linked = LinkedMessage::fromArray([
            'type' => 'reply',
            'sender' => '277570130',
            'mid' => 'mid.1',
            'chat' => '117541872',
        ]);

        $this->assertSame(277570130, $linked->senderId);
        $this->assertSame('mid.1', $linked->mid);
        $this->assertSame('117541872', $linked->chat);
    }

    #[Test]
    public function it_reads_a_linked_message_without_a_sender(): void
    {
        $linked = LinkedMessage::fromArray(['type' => 'reply', 'mid' => 'mid.1']);

        $this->assertNull($linked->sender);
        $this->assertNull($linked->senderId);
        $this->assertSame(['type' => 'reply', 'mid' => 'mid.1'], $linked->toArray());
    }

    #[Test]
    public function it_reads_a_message_with_a_linked_message_sender_as_string(): void
    {
        $message = Message::fromArray([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'link' => ['type' => 'reply', 'sender' => '277570130', 'mid' => 'mid.1'],
            'body' => ['mid' => 'm2', 'seq' => 2, 'text' => 'hi'],
        ]);

        $this->assertSame(277570130, $message->link?->senderId);
        $this->assertSame('mid.1', $message->link?->mid);
    }

    #[Test]
    public function it_roundtrips_an_error_response(): void
    {
        $error = $this->roundtrip(['code' => 'bad', 'message' => 'm', 'error' => 'detail'], Error::class);

        $this->assertInstanceOf(Error::class, $error);
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
    public function it_reads_subscription_time(): void
    {
        $sub = Subscription::fromArray(['url' => 'https://x', 'time' => 1_700_000_000]);

        $this->assertSame(1_700_000_000, $sub->time);
        $this->assertSame(1_700_000_000, $sub->toArray()['time']);
        $this->assertNull(Subscription::fromArray(['url' => 'https://x'])->time);
    }

    #[Test]
    public function it_roundtrips_uploaded_info(): void
    {
        $info = UploadedInfo::fromArray(['token' => 't']);

        $this->assertSame('t', $info->token);
        $this->assertSame(['token' => 't'], $info->toArray());
    }

    #[Test]
    public function it_rejects_uploaded_info_without_a_token(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "token" must be a string.');

        UploadedInfo::fromArray([]);
    }

    #[Test]
    public function it_roundtrips_upload_endpoint(): void
    {
        $endpoint = UploadEndpoint::fromArray(['url' => 'https://u', 'token' => 't']);

        $this->assertSame('https://u', $endpoint->url);
        $this->assertSame('t', $endpoint->token);
        $this->assertSame(['url' => 'https://u', 'token' => 't'], $endpoint->toArray());
        $this->assertNull(UploadEndpoint::fromArray(['url' => 'https://u'])->token);
    }

    #[Test]
    public function it_roundtrips_an_add_chat_members_result(): void
    {
        $result = $this->roundtrip([
            'success' => true,
            'failed_user_ids' => [5],
            'failed_user_details' => [['user_id' => 5, 'error' => 'blocked']],
        ], ModifyMembersResult::class);

        $this->assertInstanceOf(ModifyMembersResult::class, $result);
        $this->assertSame([5], $result->failedUserIds);
        $this->assertSame('blocked', $result->failedUserDetails[0]->error);
    }

    #[Test]
    public function it_roundtrips_a_failed_user_details(): void
    {
        $this->assertSame(['user_id' => 5, 'error' => 'blocked'], (new FailedUserDetails(5, 'blocked'))->toArray());
    }

    #[Test]
    public function it_reads_the_specification_shape_of_failed_user_details(): void
    {
        $details = FailedUserDetails::fromArray([
            'error_code' => 'add.participant.privacy',
            'user_ids' => [5, 6],
        ]);

        $this->assertSame('add.participant.privacy', $details->errorCode);
        $this->assertSame([5, 6], $details->userIds);
        $this->assertSame(0, $details->userId);
        $this->assertSame('', $details->error);
        $this->assertSame(
            ['error_code' => 'add.participant.privacy', 'user_ids' => [5, 6]],
            $details->toArray(),
        );
    }

    #[Test]
    public function it_reads_the_production_shape_of_failed_user_details(): void
    {
        $details = FailedUserDetails::fromArray(['user_id' => 5, 'error' => 'blocked']);

        $this->assertSame(5, $details->userId);
        $this->assertSame('blocked', $details->error);
        $this->assertNull($details->errorCode);
        $this->assertNull($details->userIds);
        $this->assertSame(['user_id' => 5, 'error' => 'blocked'], $details->toArray());
    }

    #[Test]
    public function it_roundtrips_list_result_wrappers(): void
    {
        $chat = ['chat_id' => 1, 'type' => 'chat', 'status' => 'active', 'last_event_time' => 1, 'participants_count' => 1, 'is_public' => false];
        $member = [...self::USER_ARRAY, 'last_access_time' => 1, 'is_owner' => false, 'is_admin' => false, 'join_time' => 1];

        $chats = ChatList::fromArray(['chats' => [$chat], 'marker' => 1]);
        $members = ChatMembersList::fromArray(['members' => [$member], 'marker' => 2]);
        $admins = ChatAdminsResult::fromArray(['members' => [$member], 'marker' => 3]);
        $updates = UpdateList::fromArray(['updates' => [['update_type' => 'message_created', 'timestamp' => 1, 'chat_id' => 1, 'message' => ['recipient' => ['chat_id' => 1], 'timestamp' => 1, 'body' => ['mid' => 'm', 'seq' => 1]]]], 'marker' => 4]);

        $this->assertSame(1, ChatList::fromArray($chats->toArray())->marker);
        $this->assertSame(2, ChatMembersList::fromArray($members->toArray())->marker);
        $this->assertSame(3, ChatAdminsResult::fromArray($admins->toArray())->marker);
        $this->assertSame(4, UpdateList::fromArray($updates->toArray())->marker);
        $this->assertCount(1, $admins->members);
        $this->assertCount(1, $updates->updates);
    }

    #[Test]
    public function it_roundtrips_request_bodies(): void
    {
        $recipient = new Recipient(chatId: 5, chatType: ChatType::Dialog);
        $this->assertSame(['chat_id' => 5, 'chat_type' => 'dialog'], $recipient->toArray());
        $this->assertSame(ChatType::Dialog, Recipient::fromArray(['chat_id' => 5, 'chat_type' => 'dialog'])->chatType);
        $this->assertNull((new Recipient())->chatId);

        $pin = new PinMessageBody('m1', notify: true);
        $this->assertSame(['message_id' => 'm1', 'notify' => true], $pin->toArray());

        $edit = new ChatPatch(title: 'T', icon: new ChatIcon('https://i.jpg'), pin: 'm1', notify: true);
        $this->assertSame(['title' => 'T', 'icon' => ['url' => 'https://i.jpg'], 'pin' => 'm1', 'notify' => true], $edit->toArray());

        $success = new SimpleQueryResult(true, 'done');
        $this->assertSame(['success' => true, 'message' => 'done'], $success->toArray());

        $command = new BotCommand('start', 'desc');
        $this->assertSame(['name' => 'start', 'description' => 'desc'], $command->toArray());

        $link = new NewMessageLink(MessageLinkType::Reply, 'm1');
        $this->assertSame(['type' => 'reply', 'mid' => 'm1'], $link->toArray());

        $callback = new Callback('c1', 'payload');
        $this->assertSame('c1', $callback->callbackId);
        $this->assertSame('payload', $callback->toArray()['payload']);
    }

    #[Test]
    public function it_reads_callback_timestamp_and_user(): void
    {
        $callback = Callback::fromArray([
            'callback_id' => 'c1',
            'timestamp' => 1_700_000_000,
            'user' => self::USER_ARRAY,
        ]);

        $this->assertSame(1_700_000_000, $callback->timestamp);
        $this->assertSame(1, $callback->user?->userId);
        $this->assertSame(1_700_000_000, $callback->toArray()['timestamp']);
        $this->assertSame(1, $callback->toArray()['user']['user_id']);
        $this->assertNull(Callback::fromArray(['callback_id' => 'c2'])->timestamp);
        $this->assertNull(Callback::fromArray(['callback_id' => 'c2'])->user);
    }

    #[Test]
    public function it_roundtrips_a_new_message_body_without_attachments(): void
    {
        $body = NewMessageBody::fromArray(['text' => 'only text'])->toArray();

        $this->assertSame(['text' => 'only text'], $body);
        $this->assertNull(NewMessageBody::fromArray([])->format);
    }

    #[Test]
    public function it_rejects_message_text_over_4000_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NewMessageBody(text: str_repeat('a', 4001));
    }

    #[Test]
    public function it_accepts_message_text_at_the_4000_character_limit(): void
    {
        $body = new NewMessageBody(text: str_repeat('a', 4000));

        $this->assertSame(4000, mb_strlen((string) $body->text));
    }

    #[Test]
    public function it_rejects_comment_text_over_4000_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        \GeekCo\MaxPhpClient\Dto\NewCommentBody::create(str_repeat('a', 4001));
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
        $link = NewMessageLink::fromArray(['type' => 'reply', 'mid' => 'm1']);

        $this->assertSame(MessageLinkType::Reply, $link->type);
        $this->assertSame('m1', $link->mid);
        $this->assertSame(['type' => 'reply', 'mid' => 'm1'], $link->toArray());
    }

    #[Test]
    public function it_rejects_a_new_message_link_without_mid(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "mid" must be a string.');

        NewMessageLink::fromArray(['type' => 'reply']);
    }

    #[Test]
    public function it_parses_an_edit_chat_body(): void
    {
        $body = ChatPatch::fromArray(['title' => 'T', 'icon' => ['url' => 'https://i.jpg'], 'pin' => 'm1', 'notify' => false]);

        $this->assertSame('T', $body->title);
        $this->assertSame('https://i.jpg', $body->icon?->url);
        $this->assertSame('m1', $body->pin);
        $this->assertFalse($body->notify);
        $this->assertNull(ChatPatch::fromArray(['title' => 'T'])->icon);
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
    public function it_roundtrips_bot_commands_result(): void
    {
        $result = $this->roundtrip(
            ['commands' => [['name' => 'start', 'description' => 'Start']]],
            BotCommandsInfo::class,
        );

        $this->assertInstanceOf(BotCommandsInfo::class, $result);
        $this->assertSame('start', $result->commands[0]->name);
        $this->assertNull(BotCommandsInfo::fromArray([])->commands);
    }

    #[Test]
    public function it_roundtrips_a_chat_icon(): void
    {
        $icon = ChatIcon::fromArray(['url' => 'https://i.jpg', 'payload' => 'p']);

        $this->assertSame(['url' => 'https://i.jpg', 'payload' => 'p'], $icon->toArray());
        $this->assertSame('https://i.jpg', ChatIcon::fromArray(['url' => 'https://i.jpg'])->url);
    }

    #[Test]
    public function it_rejects_video_info_without_a_token(): void
    {
        $this->expectException(InvalidResponseException::class);

        VideoAttachmentDetails::fromArray(['width' => 1]);
    }

    #[Test]
    public function it_rejects_an_attachment_without_a_type(): void
    {
        $this->expectException(InvalidResponseException::class);

        Attachment::fromArray(['payload' => ['url' => 'https://u']]);
    }
}
