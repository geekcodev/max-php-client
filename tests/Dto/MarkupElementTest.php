<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

use GeekCo\MaxPhpClient\Dto\Markup\EmphasizedMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\HeadingMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\HighlightedMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\LinkMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\MonospacedMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\QuoteMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\StrikethroughMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\StrongMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\UnderlineMarkup;
use GeekCo\MaxPhpClient\Dto\Markup\UserMentionMarkup;
use GeekCo\MaxPhpClient\Dto\MarkupElement;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MarkupElementTest extends TestCase
{
    /**
     * @return iterable<string, array{string, class-string<MarkupElement>}>
     */
    public static function typesProvider(): iterable
    {
        yield 'strong' => ['strong', StrongMarkup::class];
        yield 'emphasized' => ['emphasized', EmphasizedMarkup::class];
        yield 'underline' => ['underline', UnderlineMarkup::class];
        yield 'strikethrough' => ['strikethrough', StrikethroughMarkup::class];
        yield 'monospaced' => ['monospaced', MonospacedMarkup::class];
        yield 'highlighted' => ['highlighted', HighlightedMarkup::class];
        yield 'quote' => ['quote', QuoteMarkup::class];
        yield 'heading' => ['heading', HeadingMarkup::class];
    }

    #[Test]
    public function it_supports_every_documented_markup_type(): void
    {
        $this->assertSame([
            'strong',
            'emphasized',
            'underline',
            'strikethrough',
            'monospaced',
            'highlighted',
            'link',
            'quote',
            'heading',
            'user_mention',
        ], array_column(Markup::cases(), 'value'));
    }

    #[Test]
    #[DataProvider('typesProvider')]
    public function it_parses_plain_markup(string $type, string $class): void
    {
        $element = MarkupElement::fromArray(['type' => $type, 'from' => 0, 'length' => 4]);

        $this->assertInstanceOf($class, $element);
        $this->assertSame(Markup::from($type), $element->type);
        $this->assertSame(0, $element->from);
        $this->assertSame(4, $element->length);
        $this->assertSame(['type' => $type, 'from' => 0, 'length' => 4], $element->toArray());
    }

    #[Test]
    public function it_parses_link_markup(): void
    {
        $element = MarkupElement::fromArray([
            'type' => 'link',
            'from' => 5,
            'length' => 11,
            'url' => 'https://max.ru/docs',
        ]);

        $this->assertInstanceOf(LinkMarkup::class, $element);
        $this->assertSame('https://max.ru/docs', $element->url);
        $this->assertSame('https://max.ru/docs', $element->toArray()['url']);
    }

    #[Test]
    public function it_parses_user_mention_markup(): void
    {
        $element = MarkupElement::fromArray([
            'type' => 'user_mention',
            'from' => 0,
            'length' => 6,
            'user_id' => 277570130,
            'user_link' => 'https://max.ru/u/abc',
        ]);

        $this->assertInstanceOf(UserMentionMarkup::class, $element);
        $this->assertSame(277570130, $element->userId);
        $this->assertSame('https://max.ru/u/abc', $element->userLink);
    }

    #[Test]
    public function it_accepts_the_underlined_discriminator_alias(): void
    {
        $element = MarkupElement::fromArray(['type' => 'underlined', 'from' => 0, 'length' => 1]);

        $this->assertInstanceOf(UnderlineMarkup::class, $element);
        $this->assertSame('underline', $element->toArray()['type']);
    }

    #[Test]
    public function it_rejects_a_non_numeric_user_id(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "user_id" must be an integer or a numeric string, got "abc".');

        MarkupElement::fromArray(['type' => 'user_mention', 'from' => 0, 'length' => 1, 'user_id' => 'abc']);
    }

    #[Test]
    public function it_rejects_a_markup_element_without_a_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "type" must be a string.');

        MarkupElement::fromArray(['from' => 0, 'length' => 1]);
    }

    #[Test]
    public function it_rejects_an_unknown_markup_type(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "type" has unsupported value "blink".');

        MarkupElement::fromArray(['type' => 'blink', 'from' => 0, 'length' => 1]);
    }

    #[Test]
    public function it_rejects_a_markup_element_without_a_position(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "from" must be an integer.');

        MarkupElement::fromArray(['type' => 'strong', 'length' => 1]);
    }

    #[Test]
    public function it_rejects_a_markup_element_without_a_length(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "length" must be an integer.');

        MarkupElement::fromArray(['type' => 'strong', 'from' => 0]);
    }

    #[Test]
    public function it_parses_message_markup(): void
    {
        $message = Message::fromArray([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1000,
            'body' => [
                'mid' => 'm1',
                'seq' => 1,
                'text' => 'bold and link',
                'markup' => [
                    ['type' => 'strong', 'from' => 0, 'length' => 4],
                    ['type' => 'link', 'from' => 9, 'length' => 4, 'url' => 'https://max.ru'],
                ],
            ],
        ]);

        $this->assertCount(2, $message->body->markup);
        $this->assertInstanceOf(StrongMarkup::class, $message->body->markup[0]);
        $this->assertInstanceOf(LinkMarkup::class, $message->body->markup[1]);
        $this->assertSame('https://max.ru', $message->body->markup[1]->url);
    }

    #[Test]
    public function it_roundtrips_a_markup_element(): void
    {
        $data = ['type' => 'user_mention', 'from' => 3, 'length' => 6, 'user_id' => 7, 'user_link' => 'https://max.ru/u/x'];

        $decoded = MarkupElement::fromArray(MarkupElement::fromArray($data)->toArray());

        $this->assertInstanceOf(UserMentionMarkup::class, $decoded);
        $this->assertSame($data, $decoded->toArray());
    }
}
