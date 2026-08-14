<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class EditChatBody
{
    public function __construct(
        public ?string $title = null,
        public ?ChatIcon $icon = null,
        public ?string $pin = null,
        public ?bool $notify = null,
    ) {
    }

    public static function create(
        ?string $title = null,
        ?ChatIcon $icon = null,
        ?string $pin = null,
        ?bool $notify = null,
    ): self {
        return new self(
            title: $title,
            icon: $icon,
            pin: $pin,
            notify: $notify,
        );
    }

    public static function fromArray(array $data): self
    {
        $iconData = $data['icon'] ?? null;

        return new self(
            title: Json::string($data, 'title'),
            icon: \is_array($iconData) ? ChatIcon::fromArray($iconData) : null,
            pin: Json::string($data, 'pin'),
            notify: Json::bool($data, 'notify'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'icon' => $this->icon?->toArray(),
            'pin' => $this->pin,
            'notify' => $this->notify,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
