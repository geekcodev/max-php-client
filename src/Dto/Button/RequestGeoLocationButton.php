<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Button;

use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class RequestGeoLocationButton extends Button
{
    public function __construct(
        string $text,
        public ?bool $quick = null,
    ) {
        parent::__construct('request_geo_location', $text);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::requiredString($data, 'text'),
            quick: Json::bool($data, 'quick'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'quick' => $this->quick,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
