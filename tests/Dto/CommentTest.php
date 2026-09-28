<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\CommentLinkedMessage;
use GeekCo\MaxPhpClient\Dto\CommentMessage;
use GeekCo\MaxPhpClient\Dto\CommentMessageBody;
use GeekCo\MaxPhpClient\Dto\CommentMessageList;
use GeekCo\MaxPhpClient\Dto\NewCommentBody;
use GeekCo\MaxPhpClient\Dto\NewMessageLink;
use GeekCo\MaxPhpClient\Dto\SendCommentResult;
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function comment(): array
    {
        return [
            'sender' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
            'recipient' => ['chat_id' => 5, 'chat_type' => 'channel', 'post_id' => 'mid_post'],
            'timestamp' => 1000,
            'body' => ['mid' => 'c1', 'seq' => 2, 'text' => 'Nice post'],
        ];
    }

    #[Test]
    public function it_parses_a_comment(): void
    {
        $comment = CommentMessage::fromArray(self::comment());

        $this->assertSame('c1', $comment->body->mid);
        $this->assertSame(2, $comment->body->seq);
        $this->assertSame('Nice post', $comment->body->text);
        $this->assertSame(7, $comment->sender?->userId);
        $this->assertSame(5, $comment->recipient->chatId);
        $this->assertSame('channel', $comment->recipient->chatType);
        $this->assertSame('mid_post', $comment->recipient->postId);
        $this->assertNull($comment->link);
        $this->assertNull($comment->stat);
    }

    #[Test]
    public function it_parses_a_comment_published_on_behalf_of_the_channel(): void
    {
        $data = self::comment();
        $data['sender'] = null;

        $this->assertNull(CommentMessage::fromArray($data)->sender);
    }

    #[Test]
    public function it_parses_comment_markup(): void
    {
        $data = self::comment();
        $data['body']['markup'] = [
            ['type' => 'strong', 'from' => 0, 'length' => 4],
            ['type' => 'link', 'from' => 6, 'length' => 11, 'url' => 'https://max.ru'],
        ];

        $markup = CommentMessage::fromArray($data)->body->markup;

        $this->assertCount(2, $markup);
        $this->assertSame(Markup::Strong, $markup[0]->type);
        $this->assertSame(0, $markup[0]->from);
        $this->assertSame(4, $markup[0]->length);
        $this->assertSame(Markup::Link, $markup[1]->type);
        $this->assertSame('https://max.ru', $markup[1]->url);
    }

    #[Test]
    public function it_parses_a_comment_link(): void
    {
        $data = self::comment();
        $data['link'] = [
            'type' => 'reply',
            'message' => ['mid' => 'c0', 'seq' => 1, 'text' => 'Original'],
            'sender' => ['user_id' => 8, 'first_name' => 'Bob', 'is_bot' => false, 'last_activity_time' => 1000],
            'chat_id' => 5,
        ];

        $link = CommentMessage::fromArray($data)->link;

        $this->assertInstanceOf(CommentLinkedMessage::class, $link);
        $this->assertSame('reply', $link->type);
        $this->assertSame('c0', $link->message->mid);
        $this->assertSame('Original', $link->message->text);
        $this->assertSame(8, $link->sender?->userId);
        $this->assertSame(5, $link->chatId);
    }

    #[Test]
    public function it_parses_a_comment_link_published_on_behalf_of_the_channel(): void
    {
        $data = self::comment();
        $data['link'] = [
            'type' => 'forward',
            'message' => ['mid' => 'c0', 'seq' => 1],
            'sender' => null,
        ];

        $link = CommentMessage::fromArray($data)->link;

        $this->assertNull($link?->sender);
        $this->assertNull($link?->chatId);
    }

    #[Test]
    public function it_parses_comment_statistics(): void
    {
        $data = self::comment();
        $data['stat'] = ['views' => 42];

        $this->assertSame(42, CommentMessage::fromArray($data)->stat?->views);
    }

    #[Test]
    public function it_rejects_a_comment_without_a_body(): void
    {
        $data = self::comment();
        unset($data['body']);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "body" must be an object.');

        CommentMessage::fromArray($data);
    }

    #[Test]
    public function it_rejects_a_comment_without_a_recipient(): void
    {
        $data = self::comment();
        unset($data['recipient']);

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "recipient" must be an object.');

        CommentMessage::fromArray($data);
    }

    #[Test]
    public function it_rejects_a_comment_link_without_a_message(): void
    {
        $data = self::comment();
        $data['link'] = ['type' => 'reply'];

        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "message" must be an object.');

        CommentMessage::fromArray($data);
    }

    #[Test]
    public function it_parses_a_list_of_comments(): void
    {
        $list = CommentMessageList::fromArray(['messages' => [self::comment(), self::comment()]]);

        $this->assertCount(2, $list->messages);
        $this->assertSame('c1', $list->messages[0]->body->mid);
    }

    #[Test]
    public function it_parses_an_empty_list_of_comments(): void
    {
        $this->assertSame([], CommentMessageList::fromArray([])->messages);
        $this->assertSame(['messages' => []], CommentMessageList::fromArray([])->toArray());
    }

    #[Test]
    public function it_parses_a_send_comment_result(): void
    {
        $result = SendCommentResult::fromArray(['message' => self::comment()]);

        $this->assertSame('c1', $result->message->body->mid);
        $this->assertSame(['message' => $result->message->toArray()], $result->toArray());
    }

    #[Test]
    public function it_rejects_a_send_comment_result_without_a_message(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Expected a JSON object in response field "message".');

        SendCommentResult::fromArray(['message' => 'oops']);
    }

    #[Test]
    public function it_builds_a_new_comment_body(): void
    {
        $body = NewCommentBody::create(
            '**bold** text',
            TextFormat::Markdown,
            new NewMessageLink('reply', 'mid_post'),
        );

        $this->assertSame([
            'text' => '**bold** text',
            'format' => 'markdown',
            'link' => ['type' => 'reply', 'mid' => 'mid_post'],
        ], $body->toArray());
    }

    #[Test]
    public function it_parses_a_new_comment_body(): void
    {
        $body = NewCommentBody::fromArray([
            'text' => 'text',
            'format' => 'html',
            'link' => ['type' => 'forward', 'mid' => 'mid_1'],
        ]);

        $this->assertSame('text', $body->text);
        $this->assertSame(TextFormat::Html, $body->format);
        $this->assertSame('mid_1', $body->link?->mid);
    }

    #[Test]
    public function it_returns_an_empty_array_for_an_empty_comment_body(): void
    {
        $this->assertSame([], NewCommentBody::create()->toArray());
        $this->assertNull(NewCommentBody::create()->link);
    }

    #[Test]
    public function it_roundtrips_a_comment(): void
    {
        $data = self::comment();
        $data['body']['markup'] = [['type' => 'quote', 'from' => 0, 'length' => 4]];
        $data['link'] = [
            'type' => 'reply',
            'message' => ['mid' => 'c0', 'seq' => 1, 'text' => 'Original'],
        ];
        $data['stat'] = ['views' => 1];

        $decoded = CommentMessage::fromArray(CommentMessage::fromArray($data)->toArray());

        $this->assertSame('c1', $decoded->body->mid);
        $this->assertSame(Markup::Quote, $decoded->body->markup[0]->type);
        $this->assertSame('c0', $decoded->link?->message->mid);
        $this->assertSame(1, $decoded->stat?->views);
    }

    #[Test]
    public function it_keeps_a_comment_body_required_fields(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "mid" must be a string.');

        CommentMessageBody::fromArray(['seq' => 1]);
    }
}
