<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

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
        return new self(
            rows: Json::map($data, 'rows', static fn (mixed $item): InlineKeyboardButtonRow => InlineKeyboardButtonRow::fromArray((array) $item)) ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'rows' => array_map(
                static fn (InlineKeyboardButtonRow $row): array => $row->toArray(),
                $this->rows,
            ),
        ];
    }
}
