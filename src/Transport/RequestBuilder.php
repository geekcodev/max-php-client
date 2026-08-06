<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Transport;

use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;

final class RequestBuilder
{
    public function __construct(
        private readonly UriFactoryInterface $uriFactory,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUri,
        private readonly string $accessToken,
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
        string|StreamInterface|null $rawBody = null,
        array $headers = [],
        ?string $absoluteUri = null,
    ): RequestInterface {
        $uri = $this->uri($absoluteUri ?? $this->baseUri . $path, $query);

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Authorization', $this->accessToken)
            ->withHeader('Accept', 'application/json');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($jsonBody !== null) {
            $encoded = json_encode($jsonBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new InvalidArgumentException('Unable to encode the request body as JSON.');
            }

            $request = $request->withHeader('Content-Type', 'application/json');
            $request = $request->withBody($this->streamFactory->createStream($encoded));
        } elseif ($rawBody !== null) {
            $request = $request->withBody(
                $rawBody instanceof StreamInterface ? $rawBody : $this->streamFactory->createStream($rawBody),
            );
        }

        return $request;
    }

    /**
     * @param array<string, int|string|bool|float|list<int|string|bool|float>|null> $query
     */
    private function uri(string $uriString, array $query): \Psr\Http\Message\UriInterface
    {
        $uri = $this->uriFactory->createUri($uriString);

        if ($uri->getScheme() !== 'https') {
            throw new InvalidArgumentException(
                sprintf('Unsupported URI scheme "%s", only https is allowed.', $uri->getScheme()),
            );
        }

        if ($query !== []) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        return $uri;
    }
}
