<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

/**
 * Идентификация пользователя и диалога из верифицированных стартовых данных мини-приложения.
 */
readonly class WebAppIdentity
{
    public function __construct(
        public ?int $userId = null,
        public ?int $chatId = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'chat_id' => $this->chatId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
