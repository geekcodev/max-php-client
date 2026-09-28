<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;

readonly class SendCommentResult
{
    public function __construct(
        public CommentMessage $message,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        if (!\is_array($messageData)) {
            throw new InvalidResponseException('Expected a JSON object in response field "message".');
        }

        return new self(
            message: CommentMessage::fromArray($messageData),
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message->toArray(),
        ];
    }
}
