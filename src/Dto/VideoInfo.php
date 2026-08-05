<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class VideoInfo
{
    public function __construct(
        public string $videoToken,
        public string $fileName,
        public int $duration,
        public int $size,
        public VideoUrls $url,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $urlData = $data['url'] ?? null;
        if (!\is_array($urlData)) {
            throw new InvalidResponseException('Field "url" must be an object.');
        }

        return new self(
            videoToken: Json::requiredString($data, 'video_token'),
            fileName: Json::requiredString($data, 'file_name'),
            duration: Json::requiredInt($data, 'duration'),
            size: Json::requiredInt($data, 'size'),
            url: VideoUrls::fromArray($urlData),
        );
    }

    public function toArray(): array
    {
        return [
            'video_token' => $this->videoToken,
            'file_name' => $this->fileName,
            'duration' => $this->duration,
            'size' => $this->size,
            'url' => $this->url->toArray(),
        ];
    }
}
