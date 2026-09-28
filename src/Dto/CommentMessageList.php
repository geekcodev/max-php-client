<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class CommentMessageList
{
    /**
     * @param list<CommentMessage> $messages
     */
    public function __construct(
        public array $messages,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            messages: Json::map($data, 'messages', static fn (mixed $item): CommentMessage => CommentMessage::fromArray((array) $item)) ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'messages' => array_map(static fn (CommentMessage $message): array => $message->toArray(), $this->messages),
        ];
    }
}
