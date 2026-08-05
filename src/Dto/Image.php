<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class Image
{
    public function __construct(
        public string $url,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::requiredString($data, 'url'),
        );
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
        ];
    }
}
