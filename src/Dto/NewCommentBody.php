<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Тело нового комментария (`POST` и `PUT /messages/{messageId}/comments`).
 * В отличие от `NewMessageBody`, вложения не поддерживаются. Текст — до 4000
 * символов, лимит проверяет API.
 */
readonly class NewCommentBody
{
    public function __construct(
        public ?string $text = null,
        public ?TextFormat $format = null,
        public ?NewMessageLink $link = null,
    ) {
    }

    public static function create(
        ?string $text = null,
        ?TextFormat $format = null,
        ?NewMessageLink $link = null,
    ): self {
        return new self(
            text: $text,
            format: $format,
            link: $link,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::string($data, 'text'),
            format: Json::enum(TextFormat::class, $data, 'format'),
            link: \is_array($data['link'] ?? null) ? NewMessageLink::fromArray($data['link']) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'format' => $this->format?->value,
            'link' => $this->link?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
