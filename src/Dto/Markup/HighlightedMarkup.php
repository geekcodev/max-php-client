<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Markup;

use GeekCo\MaxPhpClient\Dto\MarkupElement;
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class HighlightedMarkup extends MarkupElement
{
    public function __construct(int $from, int $length)
    {
        parent::__construct(Markup::Highlighted, $from, $length);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            from: Json::requiredInt($data, 'from'),
            length: Json::requiredInt($data, 'length'),
        );
    }
}
