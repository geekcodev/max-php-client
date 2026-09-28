<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\NewCommentBody;
use GeekCo\MaxPhpClient\Dto\NewMessageLink;
use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Tests\Support\MockHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiClientCommentsTest extends TestCase
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
        return $this->factory->createResponse($status, '')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
    }

    private const COMMENT = [
        'sender' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
        'recipient' => ['chat_id' => 5, 'chat_type' => 'channel', 'post_id' => 'mid_post'],
        'timestamp' => 1000,
        'body' => ['mid' => 'c1', 'seq' => 2, 'text' => 'Nice post'],
    ];

    #[Test]
    public function it_gets_a_comment_list(): void
    {
        $this->http->next(fn ($request) => $this->json(['messages' => [self::COMMENT]]));

        $list = $this->client()->getComments('mid_post', ['c1', 'c2'], after: 10, before: 20, count: 50);

        $request = $this->http->requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/messages/mid_post/comments', $request->getUri()->getPath());
        $this->assertSame('comment_ids=c1%2Cc2&after=10&before=20&count=50', $request->getUri()->getQuery());
        $this->assertCount(1, $list->messages);
        $this->assertSame('c1', $list->messages[0]->body->mid);
    }

    #[Test]
    public function it_gets_a_comment_list_without_filters(): void
    {
        $this->http->next(fn ($request) => $this->json(['messages' => []]));

        $list = $this->client()->getComments('mid_post');

        $this->assertSame('', $this->http->requests[0]->getUri()->getQuery());
        $this->assertSame([], $list->messages);
    }

    #[Test]
    public function it_sends_a_comment(): void
    {
        $this->http->next(fn ($request) => $this->json(['message' => self::COMMENT]));

        $comment = $this->client()->sendComment(
            'mid_post',
            NewCommentBody::create('Nice post', TextFormat::Html, new NewMessageLink('reply', 'mid_post')),
            disableLinkPreview: true,
        );

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/messages/mid_post/comments', $request->getUri()->getPath());
        $this->assertSame('disable_link_preview=1', $request->getUri()->getQuery());
        $this->assertSame('{"text":"Nice post","format":"html","link":{"type":"reply","mid":"mid_post"}}', (string) $request->getBody());
        $this->assertSame('c1', $comment->body->mid);
        $this->assertSame(7, $comment->sender?->userId);
    }

    #[Test]
    public function it_sends_a_comment_without_the_link_preview_flag(): void
    {
        $this->http->next(fn ($request) => $this->json(['message' => self::COMMENT]));

        $this->client()->sendComment('mid_post', NewCommentBody::create('Nice post'));

        $request = $this->http->requests[0];
        $this->assertSame('', $request->getUri()->getQuery());
        $this->assertSame('{"text":"Nice post"}', (string) $request->getBody());
    }

    #[Test]
    public function it_rejects_an_empty_comment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A comment must contain text, formatting or a link.');

        $this->client()->sendComment('mid_post', NewCommentBody::create());
    }

    #[Test]
    public function it_edits_a_comment(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->editComment('mid_post', 'c1', NewCommentBody::create('Edited', TextFormat::Html));

        $request = $this->http->requests[0];
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('/messages/mid_post/comments', $request->getUri()->getPath());
        $this->assertSame('comment_id=c1', $request->getUri()->getQuery());
        $this->assertSame('{"text":"Edited","format":"html"}', (string) $request->getBody());
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_rejects_an_empty_comment_edit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A comment must contain text, formatting or a link.');

        $this->client()->editComment('mid_post', 'c1', NewCommentBody::create());
    }

    #[Test]
    public function it_deletes_a_comment(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $result = $this->client()->deleteComment('mid_post', 'c1');

        $request = $this->http->requests[0];
        $this->assertSame('DELETE', $request->getMethod());
        $this->assertSame('/messages/mid_post/comments', $request->getUri()->getPath());
        $this->assertSame('comment_id=c1', $request->getUri()->getQuery());
        $this->assertSame('', (string) $request->getBody());
        $this->assertTrue($result->success);
    }

    #[Test]
    public function it_gets_a_comment_by_id(): void
    {
        $this->http->next(fn ($request) => $this->json(self::COMMENT));

        $comment = $this->client()->getComment('mid_post', 'c1');

        $request = $this->http->requests[0];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('/messages/mid_post/comments/c1', $request->getUri()->getPath());
        $this->assertSame('c1', $comment->body->mid);
    }

    #[Test]
    public function it_rejects_a_message_id_with_forbidden_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message_id must match [a-zA-Z0-9_-]+.');

        $this->client()->getComments('mid post/../secret');
    }

    #[Test]
    public function it_rejects_a_comment_id_with_forbidden_characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('comment_id must match [a-zA-Z0-9_-]+.');

        $this->client()->deleteComment('mid_post', 'c1&count=100');
    }

    #[Test]
    public function it_sends_an_answer_with_a_disabled_link_preview(): void
    {
        $this->http->next(fn ($request) => $this->json(['success' => true]));

        $this->client()->sendAnswer('c1', notification: 'Готово', disableLinkPreview: true);

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('callback_id=c1&disable_link_preview=1', $request->getUri()->getQuery());
        $this->assertSame('{"notification":"Готово"}', (string) $request->getBody());
    }
}
