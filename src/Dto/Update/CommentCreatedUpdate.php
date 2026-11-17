<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto\Update;

use GeekCo\MaxPhpClient\Dto\CommentMessage;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Internal\Json;

final readonly class CommentCreatedUpdate extends Update
{
    public function __construct(
        int $timestamp,
        public CommentMessage $message,
    ) {
        parent::__construct('comment_created', $timestamp);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: Json::requiredInt($data, 'timestamp'),
            message: self::requiredComment($data),
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
