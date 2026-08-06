<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Transport\RequestBuilder;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RequestBuilderTest extends TestCase
{
    private function builder(): RequestBuilder
    {
        $factory = new HttpFactory();

        return new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'secret-token');
    }

    #[Test]
    public function it_builds_a_json_request_with_headers(): void
    {
        $request = $this->builder()->request('POST', '/messages', ['chat_id' => 5], ['text' => 'hi']);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://platform-api2.max.ru/messages?chat_id=5', (string) $request->getUri());
        $this->assertSame('secret-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame('{"text":"hi"}', (string) $request->getBody());
    }

    #[Test]
    public function it_builds_a_raw_body_request_with_custom_headers(): void
    {
        $request = $this->builder()->request(
            'POST',
            '/uploads',
            ['type' => 'file'],
            rawBody: 'raw-data',
            headers: ['Content-Type' => 'multipart/form-data; boundary=xyz'],
        );

        $this->assertSame('multipart/form-data; boundary=xyz', $request->getHeaderLine('Content-Type'));
        $this->assertSame('raw-data', (string) $request->getBody());
    }

    #[Test]
    public function it_builds_a_raw_body_request_from_a_stream(): void
    {
        $factory = new HttpFactory();
        $stream = $factory->createStream('stream-data');

        $request = $this->builder()->request(
            'POST',
            '/uploads',
            ['type' => 'file'],
            rawBody: $stream,
            headers: ['Content-Type' => 'multipart/form-data; boundary=xyz'],
        );

        $this->assertSame('multipart/form-data; boundary=xyz', $request->getHeaderLine('Content-Type'));
        $this->assertSame($stream, $request->getBody());
        $this->assertSame('stream-data', (string) $request->getBody());
    }

    #[Test]
    public function it_rejects_non_https_uris(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->request('GET', '/me', absoluteUri: 'http://insecure.example.com/me');
    }

    #[Test]
    public function it_rejects_an_unencodable_json_body(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder()->request('POST', '/messages', [], ['value' => INF]);
    }
}
