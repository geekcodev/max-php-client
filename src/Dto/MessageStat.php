<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class MessageStat
{
    public function __construct(
        public int $views,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            views: Json::requiredInt($data, 'views'),
        );
    }

    public function toArray(): array
    {
        return [
            'views' => $this->views,
        ];
    }
}
