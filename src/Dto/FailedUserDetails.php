<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class FailedUserDetails
{
    public function __construct(
        public int $userId,
        public string $error,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: Json::requiredInt($data, 'user_id'),
            error: Json::requiredString($data, 'error'),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'error' => $this->error,
        ];
    }
}
