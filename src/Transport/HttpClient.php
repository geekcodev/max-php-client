<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Transport;

use GeekCo\MaxPhpClient\Exception\MaxApiException;
use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

final class HttpClient
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestBuilder $requestBuilder,
        private readonly ResponseDecoder $responseDecoder,
        private readonly RetryStrategy $retryStrategy,
    ) {
    }

    /**
     * @param array<string, int|string|bool|float|list<int|string|bool|float>|null> $query
     * @param array<mixed>|null $jsonBody
     * @param array<string, string> $headers
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $jsonBody = null,
        ?string $rawBody = null,
        array $headers = [],
        ?string $absoluteUri = null,
    ): mixed {
        $attempt = 0;

        while (true) {
            $request = $this->requestBuilder->request(
                $method,
                $path,
                $query,
                $jsonBody,
                $rawBody,
                $headers,
                $absoluteUri,
            );

            try {
                $response = $this->httpClient->sendRequest($request);
                $result = $this->responseDecoder->decode($response);

                return $result;
            } catch (ClientExceptionInterface $e) {
                throw new NetworkException('Network error: ' . $e->getMessage(), 0, $e);
            } catch (MaxApiException $e) {
                if (!$this->canRetry($e, $method, $attempt)) {
                    throw $e;
                }
            }

            $this->wait($this->retryStrategy->delayForAttempt($attempt));
            ++$attempt;
        }
    }

    private function canRetry(MaxApiException $exception, string $method, int $attempt): bool
    {
        return $attempt + 1 < $this->retryStrategy->maxAttempts
            && $this->retryStrategy->shouldRetry($exception, $method);
    }

    protected function wait(float $seconds): void
    {
        usleep((int) ($seconds * 1_000_000));
    }
}
