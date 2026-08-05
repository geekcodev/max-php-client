<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GeekCo\MaxPhpClient\Dto\ErrorResponse;
use GeekCo\MaxPhpClient\Exception\ApiException;
use GeekCo\MaxPhpClient\Exception\AttachmentNotReadyException;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Exception\RateLimitException;
use GeekCo\MaxPhpClient\Transport\ResponseDecoder;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResponseDecoderTest extends TestCase
{
    private HttpFactory $factory;
    private ResponseDecoder $decoder;

    protected function setUp(): void
    {
        $this->factory = new HttpFactory();
        $this->decoder = new ResponseDecoder();
    }

    #[Test]
    public function it_decodes_a_json_object(): void
    {
        $response = $this->response(200, '{"a":1}');

        $this->assertSame(['a' => 1], $this->decoder->decode($response));
    }

    #[Test]
    public function it_decodes_a_json_string(): void
    {
        $response = $this->response(200, '"hi"');

        $this->assertSame('hi', $this->decoder->decode($response));
    }

    #[Test]
    public function it_returns_null_for_an_empty_body(): void
    {
        $this->assertNull($this->decoder->decode($this->response(200, '')));
    }

    #[Test]
    public function it_rejects_invalid_json(): void
    {
        $this->expectException(InvalidResponseException::class);

        $this->decoder->decode($this->response(200, '{broken'));
    }

    #[Test]
    public function it_rejects_an_unexpected_json_type(): void
    {
        $this->expectException(InvalidResponseException::class);

        $this->decoder->decode($this->response(200, '42'));
    }

    #[Test]
    public function it_throws_an_api_exception_for_an_error_response(): void
    {
        $response = $this->response(400, '{"code":"bad","message":"Wrong","error":"detail"}');

        try {
            $this->decoder->decode($response);
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertSame(400, $e->statusCode);
            $this->assertSame('Wrong', $e->getMessage());
            $this->assertInstanceOf(ErrorResponse::class, $e->getError());
            $this->assertSame('bad', $e->getErrorCode());
            $this->assertSame('detail', $e->getErrorValue());
        }
    }

    #[Test]
    public function it_passes_the_reason_phrase_as_the_previous_exception(): void
    {
        try {
            $this->decoder->decode($this->response(400, '', 'Custom reason'));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertSame('Bad request', $e->getMessage());
            $this->assertSame('Custom reason', $e->getPrevious()?->getMessage());
            $this->assertNull($e->getError());
        }
    }

    #[Test]
    public function it_uses_a_status_based_message_when_the_reason_is_empty(): void
    {
        try {
            $this->decoder->decode($this->response(503, ''));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertSame('Service unavailable', $e->getMessage());
        }
    }

    #[Test]
    #[DataProvider('statusPhraseProvider')]
    public function it_maps_status_codes_to_phrase(int $status, string $phrase): void
    {
        try {
            $this->decoder->decode($this->response($status, ''));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertSame($status, $e->statusCode);
            $this->assertSame($phrase, $e->getMessage());
        }
    }

    public static function statusPhraseProvider(): array
    {
        return [
            '400' => [400, 'Bad request'],
            '401' => [401, 'Unauthorized'],
            '404' => [404, 'Not found'],
            '405' => [405, 'Method not allowed'],
            '500' => [500, 'API request failed'],
        ];
    }

    #[Test]
    public function it_ignores_a_malformed_error_body(): void
    {
        try {
            $this->decoder->decode($this->response(400, 'not json'));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertNull($e->getError());
        }
    }

    #[Test]
    public function it_ignores_a_non_object_error_body(): void
    {
        try {
            $this->decoder->decode($this->response(400, '42'));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertNull($e->getError());
        }
    }

    #[Test]
    public function it_throws_a_rate_limit_exception_for_429(): void
    {
        $response = $this->response(429, '{"code":"rate_limit","message":"slow"}')
            ->withHeader('Retry-After', '3');

        try {
            $this->decoder->decode($response);
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(3, $e->retryAfter);
        }
    }

    #[Test]
    public function it_parses_a_date_based_retry_after_header(): void
    {
        $date = gmdate(DATE_RFC7231, time() + 5);
        $response = $this->response(429, '')->withHeader('Retry-After', $date);

        try {
            $this->decoder->decode($response);
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertGreaterThanOrEqual(0, $e->retryAfter);
            $this->assertLessThanOrEqual(6, $e->retryAfter);
        }
    }

    #[Test]
    public function it_returns_null_retry_after_for_an_unparsable_header(): void
    {
        $response = $this->response(429, '')->withHeader('Retry-After', 'nonsense');

        try {
            $this->decoder->decode($response);
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertNull($e->retryAfter);
        }
    }

    #[Test]
    public function it_throws_an_attachment_not_ready_exception(): void
    {
        $response = $this->response(400, '{"code":"attachment.not.ready","message":"wait"}');

        $this->expectException(AttachmentNotReadyException::class);

        $this->decoder->decode($response);
    }

    #[Test]
    public function it_returns_null_retry_after_when_the_header_is_absent(): void
    {
        try {
            $this->decoder->decode($this->response(429, ''));
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertNull($e->retryAfter);
        }
    }

    #[Test]
    public function it_ignores_an_error_body_with_an_invalid_code(): void
    {
        try {
            $this->decoder->decode($this->response(400, '{"code":123,"message":"x"}'));
            $this->fail('Expected ApiException.');
        } catch (ApiException $e) {
            $this->assertNull($e->getError());
        }
    }

    private function response(int $status, string $body, string $reason = ''): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->factory->createResponse($status, $reason);

        if ($body !== '') {
            $response = $response->withHeader('Content-Type', 'application/json')
                ->withBody($this->factory->createStream($body));
        }

        return $response;
    }
}
