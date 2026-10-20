<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Payload вложения `type=audio`. Текстовая расшифровка (`transcription`)
 * официальной `schema.yaml` объявлена на верхнем уровне `AudioAttachment`,
 * спека max-openapi держит её в `payload`; приоритет — верхний уровень,
 * `payload` — фолбэк (см. `Attachment`, `docs/api-reference.md`, раздел 8).
 */
readonly class AudioAttachmentPayload
{
    public function __construct(
        public ?string $url = null,
        public ?string $token = null,
        public ?int $duration = null,
        public ?string $transcription = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            url: Json::string($data, 'url'),
            token: Json::string($data, 'token'),
            duration: Json::int($data, 'duration'),
            transcription: Json::string($data, 'transcription'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'token' => $this->token,
            'duration' => $this->duration,
            'transcription' => $this->transcription,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
