<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

/**
 * Контакт, полученный мини-приложением MAX через window.WebApp.requestContact()
 * и подтверждённый проверкой подписи (Security\WebAppContactVerifier).
 */
readonly class WebAppContact
{
    public function __construct(
        public ?int $userId = null,
        public ?int $authDate = null,
        public ?string $phone = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'auth_date' => $this->authDate,
            'phone' => $this->phone,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
