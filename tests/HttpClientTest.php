<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Transport\HttpClient;
use GeekCo\MaxPhpClient\Transport\RequestBuilder;
use GeekCo\MaxPhpClient\Transport\ResponseDecoder;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpClientTest extends TestCase
{
    #[Test]
    public function it_wraps_transport_errors_in_a_network_exception(): void
    {
        $factory = new HttpFactory();
        $transport = new class () implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class () extends \RuntimeException implements ClientExceptionInterface {
                };
            }
        };

        $client = new HttpClient(
            $transport,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(maxAttempts: 1),
        );

        $this->expectException(NetworkException::class);

        $client->request('GET', '/me');
    }

    #[Test]
    public function it_returns_the_decoded_result_on_success(): void
    {
        $factory = new HttpFactory();
        $transport = new class ($factory) implements ClientInterface {
            public function __construct(private HttpFactory $factory)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->factory->createStream('{"success":true}'));
            }
        };

        $client = new HttpClient(
            $transport,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(maxAttempts: 1),
        );

        $this->assertSame(['success' => true], $client->request('GET', '/me'));
    }
}
