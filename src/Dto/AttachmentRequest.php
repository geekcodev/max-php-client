<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\AttachmentType;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class AttachmentRequest
{
    /**
     * @param list<InlineKeyboardButtonRow>|null $rows
     */
    public function __construct(
        public AttachmentType $type,
        public ?string $token = null,
        public ?string $url = null,
        public ?array $rows = null,
    ) {
    }

    /**
     * @param list<InlineKeyboardButtonRow>|null $rows
     */
    public static function create(
        AttachmentType $type,
        ?string $token = null,
        ?string $url = null,
        ?array $rows = null,
    ): self {
        return new self(
            type: $type,
            token: $token,
            url: $url,
            rows: $rows,
        );
    }

    public static function fromArray(array $data): self
    {
        $payloadData = $data['payload'] ?? null;
        $payload = \is_array($payloadData) ? $payloadData : [];

        $rows = null;
        if (isset($payload['buttons']) && \is_array($payload['buttons'])) {
            $rows = array_values(array_map(static fn (mixed $item): InlineKeyboardButtonRow => InlineKeyboardButtonRow::fromArray((array) $item), $payload['buttons']));
        } elseif (isset($payload['rows']) && \is_array($payload['rows'])) {
            $rows = array_values(array_map(static fn (mixed $item): InlineKeyboardButtonRow => InlineKeyboardButtonRow::fromArray((array) $item), $payload['rows']));
        }

        return new self(
            type: Json::enum(AttachmentType::class, $data, 'type')
                ?? throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Field "type" must be a string.'),
            token: Json::string($payload, 'token'),
            url: Json::string($payload, 'url'),
            rows: $rows,
        );
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $payload = array_filter([
            'token' => $this->token,
            'url' => $this->url,
            'buttons' => $this->rows === null
                ? null
                : array_map(static fn (InlineKeyboardButtonRow $row): array => $row->toArray(), $this->rows),
        ], static fn (mixed $value): bool => $value !== null);

        return array_filter([
            'type' => $this->type->value,
            'payload' => $payload === [] ? null : $payload,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
