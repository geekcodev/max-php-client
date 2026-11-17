<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\MessageLinkType;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class NewMessageLink
{
    public function __construct(
        public MessageLinkType $type,
        public string $mid,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::enum(MessageLinkType::class, $data, 'type')
                ?? throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Field "type" must be a string.'),
            mid: Json::requiredString($data, 'mid'),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'mid' => $this->mid,
        ];
    }
}
