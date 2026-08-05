<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class NewMessageBody
{
    /**
     * @param list<AttachmentRequest>|null $attachments
     */
    public function __construct(
        public ?string $text = null,
        public ?array $attachments = null,
        public ?NewMessageLink $link = null,
        public ?bool $notify = null,
        public ?TextFormat $format = null,
    ) {
    }

    /**
     * @param list<AttachmentRequest>|null $attachments
     */
    public static function create(
        ?string $text = null,
        ?array $attachments = null,
        ?NewMessageLink $link = null,
        ?bool $notify = null,
        ?TextFormat $format = null,
    ): self {
        return new self(
            text: $text,
            attachments: $attachments,
            link: $link,
            notify: $notify,
            format: $format,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::string($data, 'text'),
            attachments: Json::map($data, 'attachments', static fn (mixed $item): AttachmentRequest => AttachmentRequest::fromArray((array) $item)),
            link: \is_array($data['link'] ?? null) ? NewMessageLink::fromArray($data['link']) : null,
            notify: Json::bool($data, 'notify'),
            format: Json::enum(TextFormat::class, $data, 'format'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'text' => $this->text,
            'attachments' => $this->attachments === null
                ? null
                : array_map(static fn (AttachmentRequest $attachment): array => $attachment->toArray(), $this->attachments),
            'link' => $this->link?->toArray(),
            'notify' => $this->notify,
            'format' => $this->format?->value,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
