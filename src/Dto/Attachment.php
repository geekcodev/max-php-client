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
 *
 * Метаданные вложения (`filename`, `size`, `duration`, `width`, `height`,
 * `thumbnail`, `transcription`) официальная `schema.yaml` объявляет на верхнем
 * уровне подтипа (video/audio/file), а спека max-openapi держит их в `payload`.
 * Поддержаны обе формы, приоритет — верхний уровень: значения читаются здесь и
 * передаются в payload-класс. `filename` дополнительно доступно как
 * `Attachment::$filename`, наружу имя файла отдаётся на верхнем уровне.
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
        public ?string $filename = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $type = Json::enum(AttachmentType::class, $data, 'type')
            ?? throw new InvalidResponseException('Field "type" must be a string.');

        $payloadData = $data['payload'] ?? null;
        $payload = \is_array($payloadData) ? self::payloadFromArray($type, self::withTopLevelMetadata($type, $data, $payloadData)) : null;

        $filename = Json::string($data, 'filename')
            ?? (\is_array($payloadData) ? Json::string($payloadData, 'filename') : null);

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
            filename: $filename,
        );
    }

    /**
     * Переносит метаданные с верхнего уровня вложения в данные payload, не
     * затирая значения, пришедшие внутри `payload` (спека max-openapi).
     *
     * @param array<mixed> $data
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    private static function withTopLevelMetadata(AttachmentType $type, array $data, array $payload): array
    {
        $fields = match ($type) {
            AttachmentType::Video => ['duration', 'width', 'height', 'thumbnail'],
            AttachmentType::Audio => ['duration', 'transcription'],
            AttachmentType::File => ['filename', 'size'],
            default => [],
        };

        foreach ($fields as $field) {
            if (!\array_key_exists($field, $payload) && \array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        return $payload;
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
            'filename' => $this->filename,
            'latitude' => $isLocation ? $this->latitude : null,
            'longitude' => $isLocation ? $this->longitude : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
