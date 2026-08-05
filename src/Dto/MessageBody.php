<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\TextFormat;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class MessageBody
{
    /**
     * @param list<Attachment>|null $attachments
     */
    public function __construct(
        public string $mid,
        public int $seq,
        public ?string $text = null,
        public ?array $attachments = null,
        public ?string $caption = null,
        public ?TextFormat $format = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mid: Json::requiredString($data, 'mid'),
            seq: Json::requiredInt($data, 'seq'),
            text: Json::string($data, 'text'),
            attachments: Json::map($data, 'attachments', static fn (mixed $item): Attachment => Attachment::fromArray((array) $item)),
            caption: Json::string($data, 'caption'),
            format: Json::enum(TextFormat::class, $data, 'format'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'mid' => $this->mid,
            'seq' => $this->seq,
            'text' => $this->text,
            'attachments' => $this->attachments === null
                ? null
                : array_map(static fn (Attachment $attachment): array => $attachment->toArray(), $this->attachments),
            'caption' => $this->caption,
            'format' => $this->format?->value,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
