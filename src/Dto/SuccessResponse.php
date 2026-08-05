<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class SuccessResponse
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: Json::requiredBool($data, 'success'),
            message: Json::string($data, 'message'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'message' => $this->message,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
