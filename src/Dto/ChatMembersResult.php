<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatMembersResult
{
    /**
     * @param list<ChatMember> $members
     */
    public function __construct(
        public array $members,
        public ?int $marker = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            members: Json::map($data, 'members', static fn (mixed $item): ChatMember => ChatMember::fromArray((array) $item)) ?? [],
            marker: Json::int($data, 'marker'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'members' => array_map(static fn (ChatMember $member): array => $member->toArray(), $this->members),
            'marker' => $this->marker,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
