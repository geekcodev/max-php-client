<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Вложение сообщения. `payload` типизирован по `type`. У `location` координаты
 * приходят на верхнем уровне (так в спецификации) либо внутри `payload` (так
 * отдаёт API, см. `docs/api-reference.md`, раздел 9) — поддержаны обе формы,
 * в `toArray()` координаты всегда отдаются на верхнем уровне.
 */
readonly class Attachment
{
    /**
     * @param ImageAttachmentPayload|VideoAttachmentPayload|AudioAttachmentPayload|FileAttachmentPayload|ContactAttachmentPayload|LocationAttachmentPayload|InlineKeyboardAttachmentPayload|PhotoAttachmentPayload|array<mixed>|null $payload
     */
    public function __construct(
        public AttachmentType $type,
        public object|array|null $payload = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $type = Json::enum(AttachmentType::class, $data, 'type')
            ?? throw new InvalidResponseException('Field "type" must be a string.');

        $payloadData = $data['payload'] ?? null;
        $payload = \is_array($payloadData) ? self::payloadFromArray($type, $payloadData) : null;

        $latitude = Json::float($data, 'latitude');
        $longitude = Json::float($data, 'longitude');
        if ($type === AttachmentType::Location) {
            $location = $payload instanceof LocationAttachmentPayload ? $payload : null;
            $latitude ??= $location?->latitude;
            $longitude ??= $location?->longitude;
        }

        return new self(
            type: $type,
            payload: $payload,
            latitude: $latitude,
            longitude: $longitude,
        );
    }

    /**
     * @return ImageAttachmentPayload|VideoAttachmentPayload|AudioAttachmentPayload|FileAttachmentPayload|ContactAttachmentPayload|LocationAttachmentPayload|InlineKeyboardAttachmentPayload|PhotoAttachmentPayload|array<mixed>
     */
    public static function payloadFromArray(AttachmentType $type, array $data): object|array
    {
        return match ($type) {
            AttachmentType::Image => ImageAttachmentPayload::fromArray($data),
            AttachmentType::Video => VideoAttachmentPayload::fromArray($data),
            AttachmentType::Audio => AudioAttachmentPayload::fromArray($data),
            AttachmentType::File => FileAttachmentPayload::fromArray($data),
            AttachmentType::Contact => ContactAttachmentPayload::fromArray($data),
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
        $isLocation = $this->type === AttachmentType::Location;

        return array_filter([
            'type' => $this->type->value,
            'payload' => $this->payload === null || $isLocation
                ? null
                : (is_object($this->payload) ? $this->payload->toArray() : $this->payload),
            'latitude' => $isLocation ? $this->latitude : null,
            'longitude' => $isLocation ? $this->longitude : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
