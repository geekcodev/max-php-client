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
use Psr\Http\Message\StreamInterface;

final class UploaderTest extends TestCase
{
    #[Test]
    public function it_performs_two_step_upload_for_file_type(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $requestBuilder = new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token');
        $client = new HttpClient($http, $requestBuilder, new ResponseDecoder(), new RetryStrategy());
        $uploader = new Uploader($client, $factory);

        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file, 'file-contents');

        // Step 1: POST /uploads?type=file → get URL
        $http->next(function ($request) {
            $this->assertSame('type=file', $request->getUri()->getQuery());
            $this->assertSame('', (string) $request->getBody());

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://fu.oneme.ru/upload.do?token=xyz'], JSON_THROW_ON_ERROR)));
        });

        // Step 2: POST file to upload URL → get token
        $http->next(function ($request) {
            $this->assertSame('https://fu.oneme.ru/upload.do?token=xyz', (string) $request->getUri());
            $body = $request->getBody();

            $this->assertSame('multipart/form-data', $request->getHeaderLine('Content-Type') !== '' ? explode(';', $request->getHeaderLine('Content-Type'))[0] : '');
            $this->assertInstanceOf(StreamInterface::class, $body);
            $this->assertTrue($body->isSeekable());
            $body->rewind();
            $this->assertStringContainsString('name="data"', (string) $body);
            $body->rewind();
            $this->assertStringContainsString('file-contents', (string) $body);
            $body->rewind();
            $first = (string) $body;
            $body->rewind();
            $this->assertSame($first, (string) $body);

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['token' => 'file-token-123'], JSON_THROW_ON_ERROR)));
        });

        $result = $uploader->upload(UploadType::File, $file);

        $this->assertSame('https://fu.oneme.ru/upload.do?token=xyz', $result->url);
        $this->assertSame('file-token-123', $result->token);

        unlink($file);
    }

    #[Test]
    public function it_uses_token_from_step2_when_provided(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );
        $uploader = new Uploader($client, $factory);

        $file = tempnam(sys_get_temp_dir(), 'max-test') . '.jpg';
        file_put_contents($file, "\xff\xd8\xff\xe0");

        // Step 1: no token in step 1 response
        $http->next(function ($request) {
            $this->assertSame('type=image', $request->getUri()->getQuery());

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://iu.oneme.ru/upload.do?...'], JSON_THROW_ON_ERROR)));
        });

        // Step 2: token returned from file upload
        $http->next(function ($request) {
            $this->assertSame('https://iu.oneme.ru/upload.do?...', (string) $request->getUri());
            $body = $request->getBody();
            $body->rewind();
            $this->assertStringContainsString('name="data"', (string) $body);
            $body->rewind();
            $this->assertStringContainsString('image/jpeg', (string) $body);

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['token' => 'img-token-abc'], JSON_THROW_ON_ERROR)));
        });

        $result = $uploader->upload(UploadType::Image, $file);

        $this->assertSame('https://iu.oneme.ru/upload.do?...', $result->url);
        $this->assertSame('img-token-abc', $result->token);

        unlink($file);
    }

    #[Test]
    public function it_falls_back_to_token_from_step1_when_step2_has_no_token(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = new HttpClient(
            $http,
            new RequestBuilder($factory, $factory, $factory, 'https://platform-api2.max.ru', 'token'),
            new ResponseDecoder(),
            new RetryStrategy(),
        );
        $uploader = new Uploader($client, $factory);

        $file = tempnam(sys_get_temp_dir(), 'max-test');
        file_put_contents($file, 'data');

        // Step 1: token returned here
        $http->next(function () {
            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://vu.okcdn.ru/upload', 'token' => 'step1-token'], JSON_THROW_ON_ERROR)));
        });

        // Step 2: no token in response
        $http->next(function () {
            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['status' => 'ok'], JSON_THROW_ON_ERROR)));
        });

        $result = $uploader->upload(UploadType::Video, $file);

        $this->assertSame('https://vu.okcdn.ru/upload', $result->url);
        $this->assertSame('step1-token', $result->token);

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

        (new Uploader($client, $factory))->upload(UploadType::Image, '/nonexistent/file.png');
    }

    #[Test]
    public function it_uses_detected_mime_type_for_unknown_extension(): void
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

        // Step 1
        $http->next(function ($request) {
            $this->assertSame('type=file', $request->getUri()->getQuery());

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://fu.oneme.ru/x'], JSON_THROW_ON_ERROR)));
        });

        // Step 2
        $http->next(function ($request) {
            $body = $request->getBody();
            $body->rewind();
            $this->assertStringContainsString('text/plain', (string) $body);

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['token' => 'tok'], JSON_THROW_ON_ERROR)));
        });

        $result = (new Uploader($client, $factory))->upload(UploadType::File, $file . '.xzzz');

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

        // Step 1
        $http->next(function () {
            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['url' => 'https://iu.oneme.ru/x'], JSON_THROW_ON_ERROR)));
        });

        // Step 2
        $http->next(function ($request) {
            $body = $request->getBody();
            $body->rewind();
            $this->assertStringContainsString('image/jpeg', (string) $body);

            return (new HttpFactory())->createResponse(200)
                ->withBody((new HttpFactory())->createStream(json_encode(['token' => 'tok'], JSON_THROW_ON_ERROR)));
        });

        $result = (new Uploader($client, $factory))->upload(UploadType::Image, $file);

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
            (new Uploader($client, $factory))->upload(UploadType::File, $file);
        } finally {
            unlink($file);
        }
    }
}
