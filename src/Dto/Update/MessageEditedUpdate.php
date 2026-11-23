<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class MessageEditedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public Message $message,
    ) {
        parent::__construct('message_edited', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            message: self::requiredMessage($data),
        );
    }

    public function toArray(): array
    {
        return [
            ...parent::toArray(),
            'message' => $this->message->toArray(),
        ];
    }
}
