<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class SimpleQueryResult
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        public ?string $code = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            success: Json::requiredBool($data, 'success'),
            message: Json::string($data, 'message'),
            code: Json::string($data, 'code'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'message' => $this->message,
            'code' => $this->code,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
