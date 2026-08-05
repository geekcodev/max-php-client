<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Transport;

use GeekCo\MaxPhpClient\Dto\ErrorResponse;
use GeekCo\MaxPhpClient\Exception\ApiException;
use GeekCo\MaxPhpClient\Exception\AttachmentNotReadyException;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Exception\RateLimitException;
use Psr\Http\Message\ResponseInterface;

final class ResponseDecoder
{
    public function decode(ResponseInterface $response): mixed
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status >= 400) {
            throw $this->errorFromResponse($response, $status, $body);
        }

        if ($body === '') {
            return null;
        }

        return $this->json($body);
    }

    private function errorFromResponse(ResponseInterface $response, int $status, string $body): ApiException
    {
        $error = null;
        $decoded = $this->tryJson($body);
        if (\is_array($decoded) && isset($decoded['code'], $decoded['message'])) {
            try {
                $error = ErrorResponse::fromArray($decoded);
            } catch (InvalidResponseException) {
                $error = null;
            }
        }

        $reason = $response->getReasonPhrase() !== '' ? $response->getReasonPhrase() : 'HTTP ' . $status;

        if ($status === 429) {
            return new RateLimitException($status, $error, $this->retryAfter($response));
        }

        if ($error !== null && $error->code === 'attachment.not.ready') {
            return new AttachmentNotReadyException($status, $error);
        }

        return new ApiException($status, $error, new \RuntimeException($reason));
    }

    private function retryAfter(ResponseInterface $response): ?int
    {
        $value = $response->getHeaderLine('Retry-After');
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return (int) $value;
        }

        $date = \DateTimeImmutable::createFromFormat(DATE_RFC7231, $value);
        if ($date === false) {
            return null;
        }

        return max(0, $date->getTimestamp() - time());
    }

    /**
     * @return array<mixed>|null
     */
    private function tryJson(string $body): ?array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<mixed>|string|null
     */
    private function json(string $body): mixed
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseException('Invalid JSON in response body.', previous: $e);
        }

        if (!\is_array($decoded) && !\is_string($decoded) && $decoded !== null) {
            throw new InvalidResponseException('Unexpected JSON type in response body.');
        }

        return $decoded;
    }
}
