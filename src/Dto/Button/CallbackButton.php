<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Button;

use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class CallbackButton extends Button
{
    public function __construct(
        string $text,
        public string $payload,
    ) {
        parent::__construct('callback', $text);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::requiredString($data, 'text'),
            payload: Json::requiredString($data, 'payload'),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'payload' => $this->payload,
        ];
    }
}
