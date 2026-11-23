<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class VideoUrls
{
    public function __construct(
        public ?string $mp4_1080 = null,
        public ?string $mp4_720 = null,
        public ?string $mp4_480 = null,
        public ?string $mp4_360 = null,
        public ?string $mp4_240 = null,
        public ?string $mp4_144 = null,
        public ?string $hls = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mp4_1080: Json::string($data, 'mp4_1080'),
            mp4_720: Json::string($data, 'mp4_720'),
            mp4_480: Json::string($data, 'mp4_480'),
            mp4_360: Json::string($data, 'mp4_360'),
            mp4_240: Json::string($data, 'mp4_240'),
            mp4_144: Json::string($data, 'mp4_144'),
            hls: Json::string($data, 'hls'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'mp4_1080' => $this->mp4_1080,
            'mp4_720' => $this->mp4_720,
            'mp4_480' => $this->mp4_480,
            'mp4_360' => $this->mp4_360,
            'mp4_240' => $this->mp4_240,
            'mp4_144' => $this->mp4_144,
            'hls' => $this->hls,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
