<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class UpdatesResult
{
    /**
     * @param list<Update> $updates
     */
    public function __construct(
        public array $updates,
        public ?int $marker = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            updates: Json::map($data, 'updates', static fn (mixed $item): Update => Update::fromArray((array) $item)) ?? [],
            marker: Json::int($data, 'marker'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'updates' => array_map(static fn (Update $update): array => $update->toArray(), $this->updates),
            'marker' => $this->marker,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
