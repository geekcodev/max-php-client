<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatIcon
{
    public function __construct(
        public string $url,
        public ?string $payload = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
            payload: Json::string($data, 'payload'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'payload' => $this->payload,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
