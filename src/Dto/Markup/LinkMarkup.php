<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Markup;

use GeekCo\MaxPhpClient\Dto\MarkupElement;
use GeekCo\MaxPhpClient\Enum\Markup;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class LinkMarkup extends MarkupElement
{
    public function __construct(
        int $from,
        int $length,
        public string $url,
    ) {
        parent::__construct(Markup::Link, $from, $length);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            from: Json::requiredInt($data, 'from'),
            length: Json::requiredInt($data, 'length'),
            url: Json::requiredString($data, 'url'),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'url' => $this->url,
        ];
    }
}
