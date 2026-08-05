<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatListResult
{
    /**
     * @param list<Chat> $chats
     */
    public function __construct(
        public array $chats,
        public ?int $marker = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            chats: Json::map($data, 'chats', static fn (mixed $item): Chat => Chat::fromArray((array) $item)) ?? [],
            marker: Json::int($data, 'marker'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'chats' => array_map(static fn (Chat $chat): array => $chat->toArray(), $this->chats),
            'marker' => $this->marker,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
