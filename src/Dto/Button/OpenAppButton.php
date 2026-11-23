<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Button;

use GeekCo\MaxPhpClient\Dto\Button;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class OpenAppButton extends Button
{
    public function __construct(
        string $text,
        public string $webApp,
        public ?string $payload = null,
        public ?int $contactId = null,
    ) {
        parent::__construct('open_app', $text);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            text: Json::requiredString($data, 'text'),
            webApp: Json::requiredString($data, 'web_app'),
            payload: Json::string($data, 'payload'),
            contactId: Json::int($data, 'contact_id'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'web_app' => $this->webApp,
            'payload' => $this->payload,
            'contact_id' => $this->contactId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
