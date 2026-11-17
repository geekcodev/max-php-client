<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Button;

use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class LinkButton extends Button
{
    public function __construct(
        string $text,
        public string $url,
    ) {
        parent::__construct('link', $text);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::requiredString($data, 'text'),
            url: Json::requiredString($data, 'url'),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'url' => $this->url,
        ];
    }
}
