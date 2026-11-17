<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Callback;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class MessageCallbackUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public Callback $callback,
        public ?Message $message = null,
        public ?string $userLocale = null,
    ) {
        parent::__construct('message_callback', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        $callbackData = Json::requiredArray($data, 'callback');
        $messageData = $data['message'] ?? $callbackData['message'] ?? null;

        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            callback: Callback::fromArray($callbackData),
            message: \is_array($messageData) ? Message::fromArray($messageData) : null,
            userLocale: Json::string($data, 'user_locale'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'callback' => $this->callback->toArray(),
            'message' => $this->message?->toArray(),
            'user_locale' => $this->userLocale,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
