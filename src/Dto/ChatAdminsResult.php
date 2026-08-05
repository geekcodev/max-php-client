<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatAdminsResult
{
    /**
     * @param list<ChatAdmin> $admins
     */
    public function __construct(
        public array $admins,
        public ?int $marker = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            admins: Json::map($data, 'admins', static fn (mixed $item): ChatAdmin => ChatAdmin::fromArray((array) $item)) ?? [],
            marker: Json::int($data, 'marker'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'admins' => array_map(static fn (ChatAdmin $admin): array => $admin->toArray(), $this->admins),
            'marker' => $this->marker,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
