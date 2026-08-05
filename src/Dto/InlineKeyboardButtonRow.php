<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class InlineKeyboardButtonRow
{
    /**
     * @param list<InlineKeyboardButton> $buttons
     */
    public function __construct(
        public array $buttons,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            buttons: Json::map($data, 'buttons', static fn (mixed $item): InlineKeyboardButton => InlineKeyboardButton::fromArray((array) $item)) ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'buttons' => array_map(
                static fn (InlineKeyboardButton $button): array => $button->toArray(),
                $this->buttons,
            ),
        ];
    }
}
