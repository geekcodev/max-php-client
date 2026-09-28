<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Тело комментария к посту в канале. В отличие от `MessageBody`, вложения
 * не поддерживаются, зато приходит разобранная разметка `markup`.
 */
readonly class CommentMessageBody
{
    /**
     * @param list<MarkupElement>|null $markup
     */
    public function __construct(
        public string $mid,
        public int $seq,
        public ?string $text = null,
        public ?array $markup = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mid: Json::requiredString($data, 'mid'),
            seq: Json::requiredInt($data, 'seq'),
            text: Json::string($data, 'text'),
            markup: Json::map($data, 'markup', static fn (mixed $item): MarkupElement => MarkupElement::fromArray((array) $item)),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'mid' => $this->mid,
            'seq' => $this->seq,
            'text' => $this->text,
            'markup' => $this->markup === null
                ? null
                : array_map(static fn (MarkupElement $element): array => $element->toArray(), $this->markup),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
