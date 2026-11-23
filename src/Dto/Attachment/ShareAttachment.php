<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Dto\ShareAttachmentPayload;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class ShareAttachment extends Attachment
{
    public function __construct(
        public ShareAttachmentPayload $payload,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $imageUrl = null,
    ) {
        parent::__construct('share');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payload: ShareAttachmentPayload::fromArray(Json::requiredArray($data, 'payload')),
            title: Json::string($data, 'title'),
            description: Json::string($data, 'description'),
            imageUrl: Json::string($data, 'image_url'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'payload' => $this->payload->toArray(),
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
