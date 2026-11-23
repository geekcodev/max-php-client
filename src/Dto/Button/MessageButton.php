<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Button;

use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class MessageButton extends Button
{
    public function __construct(string $text)
    {
        parent::__construct('message', $text);
    }

    public static function fromArray(array $data): self
    {
        return new self(text: Json::requiredString($data, 'text'));
    }

    public function toArray(): array
    {
        return parent::toArray();
    }
}
