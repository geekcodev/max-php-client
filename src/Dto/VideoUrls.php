<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Загрузка видео: `url` — наборы ссылок на файл. Официальная `schema.yaml`
 * объявляет потоки `mp4_144`…`mp4_1080` и `hls`; легаси-поля `mp4` и `preview`
 * приходят в старых ответах и сохраняются до v2.0.0
 * (см. `docs/api-reference.md`, раздел 8).
 */
readonly class VideoUrls
{
    public function __construct(
        public ?string $mp4 = null,
        public ?string $preview = null,
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
            mp4: Json::string($data, 'mp4'),
            preview: Json::string($data, 'preview'),
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
            'mp4' => $this->mp4,
            'preview' => $this->preview,
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
