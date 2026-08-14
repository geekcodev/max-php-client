<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\RateLimit\RateLimiter;
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

    #[Test]
    public function it_throttles_requests_with_the_global_rate_limiter(): void
    {
        $factory = new HttpFactory();
        $transport = new class ($factory) implements ClientInterface {
            public int $sends = 0;

            public function __construct(private HttpFactory $factory)
            {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                ++$this->sends;

                return $this->factory->createResponse(200)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->factory->createStream('{}'));
            }
        };

        $limiter = new class (30.0, 1.0) extends RateLimiter {
            public float $sleepSeconds = 0.0;

            protected function sleep(float $seconds): void
            {
                $this->sleepSeconds += $seconds;
            }
        };

        $client = new HttpClient(
            $transport,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(maxAttempts: 1),
            $limiter,
        );

        $client->request('GET', '/me');
        $client->request('GET', '/me');

        $this->assertSame(2, $transport->sends);
        $this->assertGreaterThan(0.0, $limiter->sleepSeconds);
    }
}
