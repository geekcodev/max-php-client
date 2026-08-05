<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class ErrorResponse
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $error = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: Json::requiredString($data, 'code'),
            message: Json::requiredString($data, 'message'),
            error: Json::string($data, 'error'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'message' => $this->message,
            'error' => $this->error,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
