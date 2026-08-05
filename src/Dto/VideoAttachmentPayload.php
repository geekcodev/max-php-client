<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class VideoAttachmentPayload
{
    public function __construct(
        public ?VideoUrls $url = null,
        public ?string $token = null,
        public ?int $duration = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $urlData = $data['url'] ?? null;

        return new self(
            url: \is_array($urlData) ? VideoUrls::fromArray($urlData) : null,
            token: Json::string($data, 'token'),
            duration: Json::int($data, 'duration'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url?->toArray(),
            'token' => $this->token,
            'duration' => $this->duration,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
