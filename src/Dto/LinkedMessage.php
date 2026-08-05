<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class LinkedMessage
{
    public function __construct(
        public string $type,
        public int $sender,
        public string $mid,
        public ?string $chat = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::requiredString($data, 'type'),
            sender: Json::requiredInt($data, 'sender'),
            mid: Json::requiredString($data, 'mid'),
            chat: Json::string($data, 'chat'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'sender' => $this->sender,
            'mid' => $this->mid,
            'chat' => $this->chat,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
