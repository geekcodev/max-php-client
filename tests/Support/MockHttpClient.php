<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MockHttpClient implements ClientInterface
{
    /** @var list<callable(RequestInterface): ResponseInterface> */
    private array $handlers = [];

    /** @var (callable(RequestInterface): ResponseInterface)|null */
    private mixed $lastHandler = null;

    /** @var list<RequestInterface> */
    public array $requests = [];

    public function next(callable $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        $handler = $this->handlers !== [] ? array_shift($this->handlers) : $this->lastHandler;
        if ($handler === null) {
            throw new \RuntimeException('No response configured for the request.');
        }

        $this->lastHandler = $handler;

        return $handler($request);
    }
}
