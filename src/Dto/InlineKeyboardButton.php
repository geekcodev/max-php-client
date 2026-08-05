<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\ButtonType;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class InlineKeyboardButton
{
    public function __construct(
        public ButtonType $type,
        public string $text,
        public ?string $payload = null,
        public ?string $url = null,
        public ?string $intent = null,
        public ?string $appData = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: Json::enum(ButtonType::class, $data, 'type')
                ?? throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Field "type" must be a string.'),
            text: Json::requiredString($data, 'text'),
            payload: Json::string($data, 'payload'),
            url: Json::string($data, 'url'),
            intent: Json::string($data, 'intent'),
            appData: Json::string($data, 'app_data'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'text' => $this->text,
            'payload' => $this->payload,
            'url' => $this->url,
            'intent' => $this->intent,
            'app_data' => $this->appData,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
