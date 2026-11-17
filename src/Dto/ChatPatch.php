<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ChatPatch
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?ChatIcon $icon = null,
        public ?string $pin = null,
        public ?bool $notify = null,
    ) {
    }

    public static function create(
        ?string $title = null,
        ?string $description = null,
        ?ChatIcon $icon = null,
        ?string $pin = null,
        ?bool $notify = null,
    ): self {
        return new self(
            title: $title,
            description: $description,
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
            description: Json::string($data, 'description'),
            icon: \is_array($iconData) ? ChatIcon::fromArray($iconData) : null,
            pin: Json::string($data, 'pin'),
            notify: Json::bool($data, 'notify'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon?->toArray(),
            'pin' => $this->pin,
            'notify' => $this->notify,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
