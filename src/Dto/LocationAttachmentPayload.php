<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class LocationAttachmentPayload
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: Json::float($data, 'latitude') ?? throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException(
                'Field "latitude" must be a number.',
            ),
            longitude: Json::float($data, 'longitude') ?? throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException(
                'Field "longitude" must be a number.',
            ),
        );
    }

    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
