<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Attachment;

use GeekCo\MaxPhpClient\Dto\Attachment;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class LocationAttachment extends Attachment
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        parent::__construct('location');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: Json::float($data, 'latitude') ?? throw new InvalidResponseException(
                'Field "latitude" must be a number.',
            ),
            longitude: Json::float($data, 'longitude') ?? throw new InvalidResponseException(
                'Field "longitude" must be a number.',
            ),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
