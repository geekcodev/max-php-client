<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

readonly class InlineKeyboardAttachmentPayload
{
    /**
     * @param list<InlineKeyboardButtonRow> $rows
     */
    public function __construct(
        public array $rows,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $rows = isset($data['buttons']) && \is_array($data['buttons'])
            ? $data['buttons']
            : ($data['rows'] ?? null);

        return new self(
            rows: \is_array($rows)
                ? array_values(array_map(static fn (mixed $item): InlineKeyboardButtonRow => InlineKeyboardButtonRow::fromArray((array) $item), $rows))
                : [],
        );
    }

    public function toArray(): array
    {
        return [
            'buttons' => array_map(
                static fn (InlineKeyboardButtonRow $row): array => $row->toArray(),
                $this->rows,
            ),
        ];
    }
}
