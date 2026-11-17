<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\MediaAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class AudioAttachment extends Attachment
{
    public function __construct(
        public MediaAttachmentPayload $payload,
        public ?string $transcription = null,
    ) {
        parent::__construct('audio');
    }

    public static function fromArray(array $data): self
    {
        $payloadData = Json::requiredArray($data, 'payload');

        return new self(
            payload: MediaAttachmentPayload::fromArray($payloadData),
            transcription: Json::string($data, 'transcription') ?? Json::string($payloadData, 'transcription'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
            'transcription' => $this->transcription,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
