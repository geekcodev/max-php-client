<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Exception;

use GeekCo\MaxPhpClient\Dto\ErrorResponse;

class ApiException extends MaxApiException
{
    public function __construct(
        public readonly int $statusCode,
        public readonly ?ErrorResponse $error = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $error !== null && $error->message !== '' ? $error->message : self::statusPhrase($statusCode),
            $statusCode,
            $previous,
        );
    }

    public function getError(): ?ErrorResponse
    {
        return $this->error;
    }

    public function getErrorCode(): ?string
    {
        return $this->error?->code;
    }

    public function getErrorValue(): ?string
    {
        return $this->error?->error;
    }

    private static function statusPhrase(int $status): string
    {
        return match ($status) {
            400 => 'Bad request',
            401 => 'Unauthorized',
            404 => 'Not found',
            405 => 'Method not allowed',
            429 => 'Too many requests',
            503 => 'Service unavailable',
            default => 'API request failed',
        };
    }
}
