<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

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
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

abstract readonly class MarkupElement
{
    public function __construct(
        public Markup $type,
        public int $from,
        public int $length,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $rawType = Json::requiredString($data, 'type');
        $type = $rawType === 'underlined' ? 'underline' : $rawType;
        $markup = Markup::tryFrom($type) ?? throw new InvalidResponseException(
            sprintf('Field "type" has unsupported value "%s".', $rawType),
        );

        return match ($markup) {
            Markup::Strong => StrongMarkup::fromArray($data),
            Markup::Emphasized => EmphasizedMarkup::fromArray($data),
            Markup::Underline => UnderlineMarkup::fromArray($data),
            Markup::Strikethrough => StrikethroughMarkup::fromArray($data),
            Markup::Monospaced => MonospacedMarkup::fromArray($data),
            Markup::Highlighted => HighlightedMarkup::fromArray($data),
            Markup::Link => LinkMarkup::fromArray($data),
            Markup::Quote => QuoteMarkup::fromArray($data),
            Markup::Heading => HeadingMarkup::fromArray($data),
            Markup::UserMention => UserMentionMarkup::fromArray($data),
        };
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'from' => $this->from,
            'length' => $this->length,
        ];
    }
}
