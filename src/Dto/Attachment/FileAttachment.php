<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\FileAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class FileAttachment extends Attachment
{
    public function __construct(
        public FileAttachmentPayload $payload,
        public ?string $filename = null,
        public ?int $size = null,
    ) {
        parent::__construct('file');
    }

    public static function fromArray(array $data): self
    {
        $payloadData = Json::requiredArray($data, 'payload');

        return new self(
            payload: FileAttachmentPayload::fromArray($payloadData),
            filename: Json::string($data, 'filename') ?? Json::string($payloadData, 'filename'),
            size: Json::int($data, 'size') ?? Json::int($payloadData, 'size'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
            'filename' => $this->filename,
            'size' => $this->size,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
