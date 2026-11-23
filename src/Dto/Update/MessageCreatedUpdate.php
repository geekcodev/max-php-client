<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class MessageCreatedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public Message $message,
        public ?string $userLocale = null,
    ) {
        parent::__construct('message_created', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            message: self::requiredMessage($data),
            userLocale: Json::string($data, 'user_locale'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            ...parent::toArray(),
            'message' => $this->message->toArray(),
            'user_locale' => $this->userLocale,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
