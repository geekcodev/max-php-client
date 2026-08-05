<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class Recipient
{
    public function __construct(
        public ?int $chatId = null,
        public ?int $userId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            chatId: Json::int($data, 'chat_id'),
            userId: Json::int($data, 'user_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
