<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Markup;

use GeekCo\MaxPhpClient\Dto\MarkupElement;
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class UserMentionMarkup extends MarkupElement
{
    public function __construct(
        int $from,
        int $length,
        public ?int $userId = null,
        public ?string $userLink = null,
    ) {
        parent::__construct(Markup::UserMention, $from, $length);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            from: Json::requiredInt($data, 'from'),
            length: Json::requiredInt($data, 'length'),
            userId: Json::tolerantInt($data, 'user_id'),
            userLink: Json::string($data, 'user_link'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'user_id' => $this->userId,
            'user_link' => $this->userLink,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
