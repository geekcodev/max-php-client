<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Dto\Button\CallbackButton;
use GeekCo\MaxPhpClient\Dto\Button\ClipboardButton;
use GeekCo\MaxPhpClient\Dto\Button\LinkButton;
use GeekCo\MaxPhpClient\Dto\Button\MessageButton;
use GeekCo\MaxPhpClient\Dto\Button\OpenAppButton;
use GeekCo\MaxPhpClient\Dto\Button\RequestContactButton;
use GeekCo\MaxPhpClient\Dto\Button\RequestGeoLocationButton;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

abstract readonly class Button
{
    public function __construct(
        public string $type,
        public string $text,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return match (Json::requiredString($data, 'type')) {
            'callback' => CallbackButton::fromArray($data),
            'link' => LinkButton::fromArray($data),
            'request_geo_location' => RequestGeoLocationButton::fromArray($data),
            'request_contact' => RequestContactButton::fromArray($data),
            'message' => MessageButton::fromArray($data),
            'open_app' => OpenAppButton::fromArray($data),
            'clipboard' => ClipboardButton::fromArray($data),
            default => throw new InvalidResponseException(
                sprintf('Unsupported button type "%s".', Json::requiredString($data, 'type')),
            ),
        };
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'text' => $this->text,
        ];
    }
}
