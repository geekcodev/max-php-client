<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class NewMessageLink
{
    /**
     * @param string|null $chat @deprecated Устаревшее поле: в исходящей форме идентификатор чата задаётся получателем.
     */
    public function __construct(
        public string $type,
        public ?string $mid = null,
        public ?string $chat = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::requiredString($data, 'type'),
            mid: Json::string($data, 'mid'),
            chat: Json::string($data, 'chat'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'mid' => $this->mid,
            'chat' => $this->chat,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
