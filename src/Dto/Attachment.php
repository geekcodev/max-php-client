<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class Attachment
{
    /**
     * @param ImageAttachmentPayload|VideoAttachmentPayload|AudioAttachmentPayload|FileAttachmentPayload|LocationAttachmentPayload|InlineKeyboardAttachmentPayload|PhotoAttachmentPayload|array<mixed>|null $payload
     */
    public function __construct(
        public AttachmentType $type,
        public object|array|null $payload = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $type = Json::enum(AttachmentType::class, $data, 'type')
            ?? throw new InvalidResponseException('Field "type" must be a string.');

        $payloadData = $data['payload'] ?? null;

        return new self(
            type: $type,
            payload: \is_array($payloadData) ? self::payloadFromArray($type, $payloadData) : null,
        );
    }

    /**
     * @return ImageAttachmentPayload|VideoAttachmentPayload|AudioAttachmentPayload|FileAttachmentPayload|LocationAttachmentPayload|InlineKeyboardAttachmentPayload|PhotoAttachmentPayload|array<mixed>
     */
    public static function payloadFromArray(AttachmentType $type, array $data): object|array
    {
        return match ($type) {
            AttachmentType::Image => ImageAttachmentPayload::fromArray($data),
            AttachmentType::Video => VideoAttachmentPayload::fromArray($data),
            AttachmentType::Audio => AudioAttachmentPayload::fromArray($data),
            AttachmentType::File => FileAttachmentPayload::fromArray($data),
            AttachmentType::InlineKeyboard => InlineKeyboardAttachmentPayload::fromArray($data),
            AttachmentType::Location => LocationAttachmentPayload::fromArray($data),
            AttachmentType::Sticker, AttachmentType::Share => $data,
        };
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'payload' => $this->payload === null
                ? null
                : (is_object($this->payload) ? $this->payload->toArray() : $this->payload),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
