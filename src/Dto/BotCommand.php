<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class BotCommand
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: Json::requiredString($data, 'name'),
            description: Json::string($data, 'description'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
