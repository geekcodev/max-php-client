<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\BotCommand;
use GeekCo\MaxPhpClient\Dto\EditChatBody;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\PinMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Enum\SenderAction;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Tests\Support\MockHttpClient;
use GeekCo\MaxPhpClient\Transport\HttpClient;
use GeekCo\MaxPhpClient\Transport\RequestBuilder;
use GeekCo\MaxPhpClient\Transport\ResponseDecoder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiClientEndpointsTest extends TestCase
{
    private HttpFactory $factory;
    private MockHttpClient $http;

    protected function setUp(): void
    {
        $this->factory = new HttpFactory();
        $this->http = new MockHttpClient();
    }

    private function client(): ApiClient
    {
        return ApiClient::create(
            $this->http,
            $this->factory,
            $this->factory,
            $this->factory,
            'secret-token',
            retryStrategy: new RetryStrategy(maxAttempts: 1, baseDelaySeconds: 0.01),
        );
    }

    private function json(array $payload, int $status = 200): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->factory->createResponse($status, '')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));

        return $response;
    }

    private const USER = ['user_id' => 1, 'first_name' => 'Bot', 'is_bot' => false, 'last_activity_time' => 0];

    private const CHAT_MEMBER = [
        'user_id' => 2,
        'first_name' => 'Alice',
        'is_bot' => false,
        'last_activity_time' => 10,
        'last_access_time' => 10,
        'is_owner' => false,
        'is_admin' => true,
        'join_time' => 5,
    ];

    #[Test]
    public function it_edits_bot_commands(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->editBotCommands([
            new BotCommand('start', 'Start the bot'),
            new BotCommand('help', 'Show help'),
        ]);

        $request = $this->http->requests[0];
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('/me/commands', $request->getUri()->getPath());
        $this->assertSame(
            '{"commands":[{"name":"start","description":"Start the bot"},{"name":"help","description":"Show help"}]}',
            (string) $request->getBody(),
        );
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_rejects_more_than_32_commands(): void
    {
        $commands = [];
        for ($i = 0; $i < 33; ++$i) {
            $commands[] = new BotCommand('cmd' . $i, 'desc');
        }

        $this->expectException(InvalidArgumentException::class);

        $this->client()->editBotCommands($commands);
    }

    #[Test]
    public function it_lists_chats(): void
    {
        $this->http->next(fn ($request) => $this->json(['chats' => [
            ['chat_id' => 5, 'type' => 'chat', 'status' => 'active', 'last_event_time' => 10, 'participants_count' => 3, 'is_public' => false],
        ], 'marker' => 42]));

        $result = $this->client()->getChats(marker: 10, count: 20);

        $request = $this->http->requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/chats', $request->getUri()->getPath());
        $this->assertSame('marker=10&count=20', $request->getUri()->getQuery());
        $this->assertCount(1, $result->chats);
        $this->assertSame(5, $result->chats[0]->chatId);
        $this->assertSame(42, $result->marker);
    }

    #[Test]
    public function it_gets_a_chat(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'chat_id' => 5,
            'type' => 'channel',
            'status' => 'active',
            'title' => 'News',
            'last_event_time' => 10,
            'participants_count' => 100,
            'is_public' => true,
        ]));

        $chat = $this->client()->getChat(5);

        $this->assertSame('News', $chat->title);
        $this->assertSame(100, $chat->participantsCount);
        $this->assertTrue($chat->isPublic);
    }

    #[Test]
    public function it_edits_a_chat(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'chat_id' => 5,
            'type' => 'chat',
            'status' => 'active',
            'last_event_time' => 10,
            'participants_count' => 3,
            'is_public' => false,
        ]));

        $this->client()->editChat(5, EditChatBody::create(title: 'New title'));

        $request = $this->http->requests[0];
        $this->assertSame('PATCH', $request->getMethod());
        $this->assertSame('/chats/5', $request->getUri()->getPath());
        $this->assertSame('{"title":"New title"}', (string) $request->getBody());
    }

    #[Test]
    public function it_sends_a_bot_action(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->sendBotAction(5, SenderAction::TypingOn);

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/chats/5/actions', $request->getUri()->getPath());
        $this->assertSame('{"type":"typing_on"}', (string) $request->getBody());
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_gets_the_pinned_message(): void
    {
        $this->http->next(fn ($request) => $this->json(['pin' => [
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'pinned'],
        ]]));

        $message = $this->client()->getPinnedMessage(5);

        $this->assertSame('pinned', $message?->body?->text);
        $this->assertSame('m1', $message?->body?->mid);
    }

    #[Test]
    public function it_returns_null_when_there_is_no_pinned_message(): void
    {
        $this->http->next(fn ($request) => $this->factory->createResponse(200));

        $this->assertNull($this->client()->getPinnedMessage(5));
    }

    #[Test]
    public function it_pins_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->pinMessage(5, PinMessageBody::create(messageId: 'm1', notify: true));

        $request = $this->http->requests[0];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/chats/5/pin', $request->getUri()->getPath());
        $this->assertSame('{"message_id":"m1","notify":true}', (string) $request->getBody());
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_unpins_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->unpinMessage(5);

        $this->assertSame('DELETE', $this->http->requests[0]->getMethod());
        $this->assertSame('/chats/5/pin', $this->http->requests[0]->getUri()->getPath());
    }

    #[Test]
    public function it_gets_bot_membership(): void
    {
        $this->http->next(fn ($request) => $this->json(self::CHAT_MEMBER));

        $member = $this->client()->getBotMembership(5);

        $this->assertSame(2, $member->userId);
        $this->assertTrue($member->isAdmin);
        $this->assertSame('/chats/5/members/me', $this->http->requests[0]->getUri()->getPath());
    }

    #[Test]
    public function it_removes_the_bot_from_a_chat(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->removeBotFromChat(5);

        $this->assertSame('DELETE', $this->http->requests[0]->getMethod());
        $this->assertSame('/chats/5/members/me', $this->http->requests[0]->getUri()->getPath());
    }

    #[Test]
    public function it_gets_chat_admins(): void
    {
        $this->http->next(fn ($request) => $this->json(['admins' => [
            ['user_id' => 2, 'permissions' => ['write', 'pin_message'], 'alias' => 'Admin'],
        ], 'marker' => 7]));

        $result = $this->client()->getChatAdmins(5, marker: 1, count: 10);

        $request = $this->http->requests[0];
        $this->assertSame('/chats/5/members/admins', $request->getUri()->getPath());
        $this->assertSame('marker=1&count=10', $request->getUri()->getQuery());
        $this->assertSame([ChatAdminPermission::Write, ChatAdminPermission::PinMessage], $result->admins[0]->permissions);
        $this->assertSame(7, $result->marker);
    }

    #[Test]
    public function it_adds_a_chat_admin(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->addChatAdmin(5, 2, [ChatAdminPermission::Write], alias: 'Moderator');

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/chats/5/members/admins', $request->getUri()->getPath());
        $this->assertSame('{"user_id":2,"permissions":["write"],"alias":"Moderator"}', (string) $request->getBody());
    }

    #[Test]
    public function it_removes_a_chat_admin(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->removeChatAdmin(5, 2);

        $this->assertSame('DELETE', $this->http->requests[0]->getMethod());
        $this->assertSame('/chats/5/members/admins/2', $this->http->requests[0]->getUri()->getPath());
    }

    #[Test]
    public function it_gets_chat_members(): void
    {
        $this->http->next(fn ($request) => $this->json(['members' => [self::CHAT_MEMBER], 'marker' => 3]));

        $result = $this->client()->getChatMembers(5, userIds: [2, 3], count: 10);

        $request = $this->http->requests[0];
        $this->assertSame('/chats/5/members', $request->getUri()->getPath());
        $this->assertSame('user_ids=2%2C3&count=10', $request->getUri()->getQuery());
        $this->assertCount(1, $result->members);
        $this->assertSame(3, $result->marker);
    }

    #[Test]
    public function it_adds_chat_members(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'success' => true,
            'failed_user_ids' => [5],
            'details' => [['user_id' => 5, 'reason' => 'blocked']],
        ]));

        $result = $this->client()->addChatMembers(5, [2, 3, 5]);

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/chats/5/members', $request->getUri()->getPath());
        $this->assertSame('{"user_ids":[2,3,5]}', (string) $request->getBody());
        $this->assertSame([5], $result->failedUserIds);
        $this->assertSame('blocked', $result->details[0]->reason);
    }

    #[Test]
    public function it_rejects_empty_or_oversized_member_lists(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->addChatMembers(5, []);
    }

    #[Test]
    public function it_removes_a_chat_member(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->removeChatMember(5, 2, block: true);

        $request = $this->http->requests[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/chats/5/members', $request->getUri()->getPath());
        $this->assertSame('user_id=2&block=1', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_creates_a_subscription(): void
    {
        $this->http->next(fn ($request) => $this->json(['url' => 'https://example.com/hook']));

        $subscription = $this->client()->createSubscription(
            'https://example.com/hook',
            updateTypes: ['message_created'],
            secret: 's3cret-key',
        );

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/subscriptions', $request->getUri()->getPath());
        $this->assertSame(
            '{"url":"https://example.com/hook","update_types":["message_created"],"secret":"s3cret-key"}',
            (string) $request->getBody(),
        );
        $this->assertSame('https://example.com/hook', $subscription->url);
    }

    #[Test]
    public function it_rejects_an_invalid_subscription_secret(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->createSubscription('https://example.com/hook', secret: 'bad secret!');
    }

    #[Test]
    public function it_deletes_a_subscription(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->deleteSubscription('https://example.com/hook');

        $request = $this->http->requests[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/subscriptions', $request->getUri()->getPath());
        $this->assertSame('url=https%3A%2F%2Fexample.com%2Fhook', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_edits_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'edited'],
        ]));

        $message = $this->client()->editMessage('m1', NewMessageBody::create(text: 'edited'));

        $request = $this->http->requests[0];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/messages', $request->getUri()->getPath());
        $this->assertSame('message_id=m1', $request->getUri()->getQuery());
        $this->assertSame('{"text":"edited"}', (string) $request->getBody());
        $this->assertSame('edited', $message->body?->text);
    }

    #[Test]
    public function it_deletes_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->deleteMessage('m1');

        $request = $this->http->requests[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('message_id=m1', $request->getUri()->getQuery());
    }

    #[Test]
    public function it_gets_a_message_by_id(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hello'],
        ]));

        $message = $this->client()->getMessageById('m1');

        $this->assertSame('/messages/m1', $this->http->requests[0]->getUri()->getPath());
        $this->assertSame('hello', $message->body?->text);
    }

    #[Test]
    public function it_rejects_an_invalid_message_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getMessageById('invalid id!');
    }

    #[Test]
    public function it_gets_video_info(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'video_token' => 'v1',
            'file_name' => 'clip.mp4',
            'duration' => 12,
            'size' => 1024,
            'url' => ['mp4' => 'https://vu.okcdn.ru/clip.mp4'],
        ]));

        $info = $this->client()->getVideoInfo('v1');

        $this->assertSame('/videos/v1', $this->http->requests[0]->getUri()->getPath());
        $this->assertSame('clip.mp4', $info->fileName);
        $this->assertSame('https://vu.okcdn.ru/clip.mp4', $info->url->mp4);
    }

    #[Test]
    public function it_uploads_media(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file, 'image-bytes');

        $this->http->next(fn ($request) => $this->json(['url' => 'https://iu.oneme.ru/x.jpg', 'token' => 't1']));

        try {
            $result = $this->client()->uploadMedia(UploadType::Image, $file);
        } finally {
            unlink($file);
        }

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/uploads', $request->getUri()->getPath());
        $this->assertSame('type=image', $request->getUri()->getQuery());
        $this->assertStringStartsWith('multipart/form-data;', $request->getHeaderLine('Content-Type'));
        $this->assertSame('https://iu.oneme.ru/x.jpg', $result->url);
        $this->assertSame('t1', $result->token);
    }

    #[Test]
    public function it_sends_an_answer(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->sendAnswer('c1', NewMessageBody::create(text: 'Answer'));

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/answers', $request->getUri()->getPath());
        $this->assertSame('callback_id=c1', $request->getUri()->getQuery());
        $this->assertSame('{"message":{"text":"Answer"}}', (string) $request->getBody());
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_sends_an_answer_without_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->sendAnswer('c1');

        $this->assertSame('', (string) $this->http->requests[0]->getBody());
    }

    #[Test]
    public function it_validates_updates_query_parameters(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getUpdates(limit: 1001);
    }

    #[Test]
    public function it_lists_subscriptions(): void
    {
        $this->http->next(fn ($request) => $this->json(['subscriptions' => [
            ['url' => 'https://example.com/hook', 'update_types' => ['message_created']],
        ]]));

        $subscriptions = $this->client()->getSubscriptions();

        $this->assertSame('/subscriptions', $this->http->requests[0]->getUri()->getPath());
        $this->assertCount(1, $subscriptions);
        $this->assertSame('https://example.com/hook', $subscriptions[0]->url);
        $this->assertSame(['message_created'], $subscriptions[0]->updateTypes);
    }

    #[Test]
    public function it_rejects_a_list_response_without_the_field(): void
    {
        $this->http->next(fn ($request) => $this->json([]));

        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        $this->client()->getSubscriptions();
    }

    #[Test]
    public function it_rejects_a_list_containing_a_non_object(): void
    {
        $this->http->next(fn ($request) => $this->json(['subscriptions' => [1]]));

        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        $this->client()->getSubscriptions();
    }

    #[Test]
    public function it_rejects_a_non_object_response_body(): void
    {
        $this->http->next(fn ($request) => $this->factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream('"not-an-object"')));

        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        $this->client()->getMe();
    }

    #[Test]
    public function it_rejects_a_pinned_response_without_the_pin_field(): void
    {
        $this->http->next(fn ($request) => $this->json([]));

        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        $this->client()->getPinnedMessage(5);
    }

    #[Test]
    public function it_gets_updates(): void
    {
        $this->http->next(fn ($request) => $this->json(['updates' => [[
            'update_type' => 'message_created',
            'timestamp' => 1,
            'chat_id' => 5,
            'user' => self::USER,
        ]], 'marker' => 9]));

        $result = $this->client()->getUpdates(limit: 5, timeout: 30, marker: 1, types: ['message_created', 'message_edited']);

        $request = $this->http->requests[0];
        $this->assertSame('/updates', $request->getUri()->getPath());
        $this->assertSame('limit=5&timeout=30&marker=1&types=message_created%2Cmessage_edited', $request->getUri()->getQuery());
        $this->assertSame('message_created', $result[0]->updateType->value);
        $this->assertSame(5, $result[0]->chatId);
    }

    #[Test]
    public function it_gets_messages(): void
    {
        $this->http->next(fn ($request) => $this->json(['messages' => [[
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hello'],
        ]]]));

        $result = $this->client()->getMessages(chatId: 5, count: 2);

        $request = $this->http->requests[0];
        $this->assertSame('/messages', $request->getUri()->getPath());
        $this->assertSame('chat_id=5&count=2', $request->getUri()->getQuery());
        $this->assertSame('hello', $result[0]->body?->text);
    }

    #[Test]
    public function it_sends_a_message(): void
    {
        $this->http->next(fn ($request) => $this->json([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hi'],
        ]));

        $recipient = new Recipient(chatId: 5);
        $message = $this->client()->sendMessage($recipient, NewMessageBody::create(text: 'hi'), disableLinkPreview: true);

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/messages', $request->getUri()->getPath());
        $this->assertSame('chat_id=5&disable_link_preview=1', $request->getUri()->getQuery());
        $this->assertSame('{"text":"hi"}', (string) $request->getBody());
        $this->assertSame('hi', $message->body?->text);
    }

    #[Test]
    public function it_requires_a_recipient(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->sendMessage(new Recipient(), NewMessageBody::create(text: 'hi'));
    }

    #[Test]
    public function it_rejects_an_invalid_chat_members_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getChatMembers(5, count: 101);
    }

    #[Test]
    public function it_rejects_an_oversized_member_list(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->addChatMembers(5, range(1, 101));
    }

    #[Test]
    public function it_rejects_an_invalid_updates_timeout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getUpdates(timeout: 91);
    }

    #[Test]
    public function it_rejects_an_invalid_messages_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client()->getMessages(count: 101);
    }

    #[Test]
    public function it_requires_upload_support(): void
    {
        $requestBuilder = new RequestBuilder($this->factory, $this->factory, $this->factory, ApiClient::BASE_URI, 'secret-token');
        $http = new HttpClient($this->http, $requestBuilder, new ResponseDecoder(), new RetryStrategy(maxAttempts: 1, baseDelaySeconds: 0.01));
        $client = new ApiClient($http);

        $this->expectException(\LogicException::class);

        $client->uploadMedia(UploadType::Image, '/nonexistent/file.png');
    }
}
