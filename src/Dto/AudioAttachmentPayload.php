<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class AudioAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
        public ?int $duration = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
            duration: Json::int($data, 'duration'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
            'duration' => $this->duration,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
