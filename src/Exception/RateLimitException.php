<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Exception;

use GeekCo\MaxPhpClient\Dto\ErrorResponse;

final class RateLimitException extends ApiException
{
    public function __construct(
        int $statusCode,
        ?ErrorResponse $error = null,
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($statusCode, $error, $previous);
    }
}
