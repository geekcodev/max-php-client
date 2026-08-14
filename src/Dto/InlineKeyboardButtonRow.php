<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

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
        $buttons = isset($data['buttons']) && \is_array($data['buttons'])
            ? $data['buttons']
            : $data;

        return new self(
            buttons: array_values(array_map(static fn (mixed $item): InlineKeyboardButton => InlineKeyboardButton::fromArray((array) $item), $buttons)),
        );
    }

    public function toArray(): array
    {
        return array_map(
            static fn (InlineKeyboardButton $button): array => $button->toArray(),
            $this->buttons,
        );
    }
}
