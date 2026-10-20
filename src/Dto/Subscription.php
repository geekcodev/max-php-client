<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class Subscription
{
    /**
     * @param list<string>|null $updateTypes
     */
    public function __construct(
        public string $url,
        public ?array $updateTypes = null,
        public ?int $time = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
            updateTypes: Json::arrayOfStrings($data, 'update_types'),
            time: Json::int($data, 'time'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'update_types' => $this->updateTypes,
            'time' => $this->time,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
