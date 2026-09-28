<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Dto;

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
     * @return iterable<string, array{string}>
     */
    public static function typesProvider(): iterable
    {
        foreach (Markup::cases() as $case) {
            yield $case->value => [$case->value];
        }
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
    public function it_parses_a_plain_markup_element(string $type): void
    {
        $element = MarkupElement::fromArray(['type' => $type, 'from' => 0, 'length' => 4]);

        $this->assertSame(Markup::from($type), $element->type);
        $this->assertSame(0, $element->from);
        $this->assertSame(4, $element->length);
        $this->assertNull($element->url);
        $this->assertNull($element->userId);
        $this->assertNull($element->userLink);
        $this->assertSame(['type' => $type, 'from' => 0, 'length' => 4], $element->toArray());
    }

    #[Test]
    public function it_parses_a_link_markup_element(): void
    {
        $element = MarkupElement::fromArray([
            'type' => 'link',
            'from' => 5,
            'length' => 11,
            'url' => 'https://max.ru/docs',
        ]);

        $this->assertSame(Markup::Link, $element->type);
        $this->assertSame('https://max.ru/docs', $element->url);
        $this->assertSame(5, $element->from);
    }

    #[Test]
    public function it_parses_a_user_mention_markup_element(): void
    {
        $element = MarkupElement::fromArray([
            'type' => 'user_mention',
            'from' => 0,
            'length' => 6,
            'user_id' => 277570130,
            'user_link' => 'https://max.ru/u/abc',
        ]);

        $this->assertSame(Markup::UserMention, $element->type);
        $this->assertSame(277570130, $element->userId);
        $this->assertSame('https://max.ru/u/abc', $element->userLink);
    }

    #[Test]
    public function it_accepts_a_numeric_string_user_id(): void
    {
        $element = MarkupElement::fromArray(['type' => 'user_mention', 'from' => 0, 'length' => 1, 'user_id' => '277570130']);

        $this->assertSame(277570130, $element->userId);
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

    /**
     * В спецификации discriminator отображает `underlined` в `UnderlineMarkup`,
     * тогда как само перечисление `Markup` содержит `underline`.
     */
    #[Test]
    public function it_rejects_the_discriminator_alias_used_by_the_specification(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "type" has unsupported value "underlined".');

        MarkupElement::fromArray(['type' => 'underlined', 'from' => 0, 'length' => 1]);
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
        $this->assertSame(Markup::Strong, $message->body->markup[0]->type);
        $this->assertSame('https://max.ru', $message->body->markup[1]->url);
    }

    #[Test]
    public function it_rejects_a_non_array_markup(): void
    {
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('Field "markup" must be an array.');

        Message::fromArray([
            'recipient' => ['chat_id' => 5],
            'timestamp' => 1000,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 't', 'markup' => 'strong'],
        ]);
    }

    #[Test]
    public function it_roundtrips_a_markup_element(): void
    {
        $data = ['type' => 'user_mention', 'from' => 3, 'length' => 6, 'user_id' => 7, 'user_link' => 'https://max.ru/u/x'];

        $decoded = MarkupElement::fromArray(MarkupElement::fromArray($data)->toArray());

        $this->assertSame($data, $decoded->toArray());
    }
}
