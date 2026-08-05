<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Security;

final class ContactVerifier
{
    public function __construct(
        private readonly string $accessToken,
    ) {
    }

    public function verify(string $vcfInfo, string $hash): bool
    {
        $normalized = str_replace(['\r\n', '\n'], "\n", $vcfInfo);

        return hash_equals(hash_hmac('sha256', $normalized, $this->accessToken), $hash);
    }
}
