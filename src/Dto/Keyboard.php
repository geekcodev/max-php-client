<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class Keyboard
{
    /**
     * @param list<list<Button>> $buttons
     */
    public function __construct(
        public array $buttons,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $rows = Json::requiredArray($data, 'buttons');
        $buttons = [];

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                throw new InvalidResponseException('Field "buttons" must be an array of arrays.');
            }

            $buttons[] = array_values(array_map(
                static fn (mixed $button): Button => Button::fromArray((array) $button),
                $row,
            ));
        }

        return new self($buttons);
    }

    public function toArray(): array
    {
        return [
            'buttons' => array_map(
                static fn (array $row): array => array_map(
                    static fn (Button $button): array => $button->toArray(),
                    $row,
                ),
                $this->buttons,
            ),
        ];
    }
}
