<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Элемент разобранной разметки текста сообщения или комментария. API присылает
 * «чистый» текст в `text`, а позиции и типы форматирования — здесь.
 *
 * Поля `url` заполняется только для `Markup::Link`, `userId` и `userLink` — только
 * для `Markup::UserMention`.
 */
readonly class MarkupElement
{
    public function __construct(
        public Markup $type,
        public int $from,
        public int $length,
        public ?string $url = null,
        public ?int $userId = null,
        public ?string $userLink = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::enum(Markup::class, $data, 'type')
                ?? throw new InvalidResponseException('Field "type" must be a string.'),
            from: Json::requiredInt($data, 'from'),
            length: Json::requiredInt($data, 'length'),
            url: Json::string($data, 'url'),
            userId: Json::tolerantInt($data, 'user_id'),
            userLink: Json::string($data, 'user_link'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'from' => $this->from,
            'length' => $this->length,
            'url' => $this->url,
            'user_id' => $this->userId,
            'user_link' => $this->userLink,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
