<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Tests\Support\MockHttpClient;
use GeekCo\MaxPhpClient\Upload\Uploader;
use GeekCo\MaxPhpClient\Transport\HttpClient;
use GeekCo\MaxPhpClient\Transport\RequestBuilder;
use GeekCo\MaxPhpClient\Transport\ResponseDecoder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UploaderTest extends TestCase
{
    #[Test]
    public function it_uploads_a_file_as_multipart(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $requestBuilder = new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token');
        $client = new HttpClient($http, $requestBuilder, new ResponseDecoder(), new RetryStrategy());
        $uploader = new Uploader($client);

        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file, 'file-contents');
        $name = basename($file);

        $http->next(function ($request) {
            $body = (string) $request->getBody();

            $this->assertSame('multipart/form-data', $request->getHeaderLine('Content-Type') !== '' ? explode(';', $request->getHeaderLine('Content-Type'))[0] : '');
            $this->assertStringContainsString('name="file"', $body);
            $this->assertStringContainsString('file-contents', $body);
            $this->assertSame('type=file', $request->getUri()->getQuery());

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://fu.oneme.ru/x'], JSON_THROW_ON_ERROR)));
        });

        $result = $uploader->upload(UploadType::File, $file);

        $this->assertSame('https://fu.oneme.ru/x', $result->url);
        $this->assertNotNull($name);

        unlink($file);
    }

    #[Test]
    public function it_rejects_missing_files(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );

        $this->expectException(InvalidArgumentException::class);

        (new Uploader($client))->upload(UploadType::Image, '/nonexistent/file.png');
    }

    #[Test]
    public function it_falls_back_to_octet_stream_when_the_mime_type_is_unknown(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );

        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file . '.xzzz', 'data');

        $http->next(function ($request) {
            $this->assertSame('type=file', $request->getUri()->getQuery());

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://fu.oneme.ru/x'], JSON_THROW_ON_ERROR)));
        });

        $result = (new Uploader($client))->upload(UploadType::File, $file . '.xzzz');

        $this->assertSame('https://fu.oneme.ru/x', $result->url);

        unlink($file . '.xzzz');
    }

    #[Test]
    public function it_uses_the_detected_mime_type(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );

        $file = tempnam(sys_get_temp_dir(), 'max-test') . '.jpg';
        file_put_contents($file, "\xff\xd8\xff\xe0");

        $http->next(function ($request) {
            $body = (string) $request->getBody();
            $this->assertStringContainsString('image/jpeg', $body);

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://iu.oneme.ru/x'], JSON_THROW_ON_ERROR)));
        });

        $result = (new Uploader($client))->upload(UploadType::Image, $file);

        $this->assertSame('https://iu.oneme.ru/x', $result->url);

        unlink($file);
    }

    #[Test]
    public function it_rejects_a_non_object_upload_response(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );

        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file, 'data');

        $http->next(fn ($request) => (new HttpFactory())->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody((new HttpFactory())->createStream('"not-an-object"')));

        $this->expectException(\GeekCo\MaxPhpClient\Exception\InvalidResponseException::class);

        try {
            (new Uploader($client))->upload(UploadType::File, $file);
        } finally {
            unlink($file);
        }
    }
}
