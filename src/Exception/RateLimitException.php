<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Exception;

use GeekCo\MaxPhpClient\Dto\Error;

final class RateLimitException extends ApiException
{
    public function __construct(
        int $statusCode,
        ?Error $error = null,
        public readonly ?int $retryAfter = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($statusCode, $error, $previous);
    }
}
