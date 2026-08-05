<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class NewMessageLink
{
    public function __construct(
        public string $type,
        public string $url,
        public ?string $token = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::requiredString($data, 'type'),
            url: Json::requiredString($data, 'url'),
            token: Json::string($data, 'token'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'url' => $this->url,
            'token' => $this->token,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
