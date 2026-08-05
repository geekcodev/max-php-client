<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class VideoUrls
{
    public function __construct(
        public ?string $mp4 = null,
        public ?string $preview = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mp4: Json::string($data, 'mp4'),
            preview: Json::string($data, 'preview'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'mp4' => $this->mp4,
            'preview' => $this->preview,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
