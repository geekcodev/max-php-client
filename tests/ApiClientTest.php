<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Exception\ApiException;
use GeekCo\MaxPhpClient\Exception\AttachmentNotReadyException;
use GeekCo\MaxPhpClient\Exception\RateLimitException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Tests\Support\MockHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApiClientTest extends TestCase
{
    private HttpFactory $factory;
    private MockHttpClient $http;

    protected function setUp(): void
    {
        $this->factory = new HttpFactory();
        $this->http = new MockHttpClient();
    }

    private function client(?RetryStrategy $retry = null): ApiClient
    {
        return ApiClient::create(
            $this->http,
            $this->factory,
            $this->factory,
            $this->factory,
            'secret-token',
            retryStrategy: $retry ?? new RetryStrategy(baseDelaySeconds: 0.01),
        );
    }

    #[Test]
    public function it_sends_the_access_token_without_bearer_prefix(): void
    {
        $this->http->next(fn ($request) => $this->json(['user_id' => 1, 'first_name' => 'Bot', 'is_bot' => false, 'last_activity_time' => 0]));

        $this->client()->getMe();

        $request = $this->http->requests[0];
        $this->assertSame('secret-token', $request->getHeaderLine('Authorization'));
        $this->assertSame('https://platform-api2.max.ru/me', (string) $request->getUri());
        $this->assertSame('GET', $request->getMethod());
    }

    #[Test]
    public function it_encodes_json_body_and_query(): void
    {
        $this->http->next(fn ($request) => $this->json(['message' => [
            'sender' => null,
            'recipient' => ['chat_id' => 42],
            'timestamp' => 1,
            'body' => ['mid' => 'm1', 'seq' => 1, 'text' => 'hello'],
        ]]));

        $this->client()->sendMessage(new Recipient(chatId: 42), NewMessageBody::create(text: 'hello'));

        $request = $this->http->requests[0];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('chat_id=42', $request->getUri()->getQuery());
        $this->assertSame('{"text":"hello"}', (string) $request->getBody());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function it_maps_server_errors_to_api_exception(): void
    {
        $this->http->next(fn ($request) => $this->json(['code' => 'bad.request', 'message' => 'Nope'], 400));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Nope');

        $this->client()->getMe();
    }

    #[Test]
    public function it_maps_429_to_rate_limit_exception_with_retry_after(): void
    {
        $this->http->next(fn ($request) => $this->json(['code' => 'rate.limit', 'message' => 'Slow down'], 429, ['Retry-After' => '7']));

        try {
            $this->client(new RetryStrategy(maxAttempts: 1))->getMe();
            $this->fail('Expected RateLimitException.');
        } catch (RateLimitException $e) {
            $this->assertSame(7, $e->retryAfter);
            $this->assertSame(429, $e->statusCode);
        }
    }

    #[Test]
    public function it_maps_attachment_not_ready(): void
    {
        $this->http->next(fn ($request) => $this->json(['code' => 'attachment.not.ready', 'message' => 'Wait'], 400));

        try {
            $this->client(new RetryStrategy(maxAttempts: 1))->getMe();
            $this->fail('Expected AttachmentNotReadyException.');
        } catch (AttachmentNotReadyException $e) {
            $this->assertSame('attachment.not.ready', $e->getError()?->code);
        }
    }

    #[Test]
    public function it_retries_503_on_idempotent_requests(): void
    {
        $attempts = 0;
        $this->http->next(function ($request) use (&$attempts) {
            ++$attempts;
            if ($attempts === 1) {
                return $this->json(['code' => 'unavailable', 'message' => 'down'], 503);
            }

            return $this->json(['user_id' => 1, 'first_name' => 'Bot', 'is_bot' => false, 'last_activity_time' => 0]);
        });

        $this->client()->getMe();

        $this->assertSame(2, $attempts);
    }

    #[Test]
    public function it_does_not_retry_non_idempotent_failures_by_default(): void
    {
        $attempts = 0;
        $this->http->next(function ($request) use (&$attempts) {
            ++$attempts;

            return $this->json(['code' => 'unavailable', 'message' => 'down'], 503);
        });

        $this->expectException(ApiException::class);

        $this->client()->sendMessage(new Recipient(chatId: 1), NewMessageBody::create(text: 'hi'));

        $this->assertSame(1, $attempts);
    }

    #[Test]
    public function it_parses_updates_from_long_polling(): void
    {
        $this->http->next(fn ($request) => $this->json(['updates' => [
            [
                'update_type' => 'message_created',
                'timestamp' => 1000,
                'chat_id' => 5,
                'user' => ['user_id' => 7, 'first_name' => 'Alice', 'is_bot' => false, 'last_activity_time' => 1000],
                'message' => [
                    'sender' => null,
                    'recipient' => ['chat_id' => 5],
                    'timestamp' => 1000,
                    'body' => ['mid' => 'a', 'seq' => 1, 'text' => 'hi'],
                ],
            ],
        ]]));

        $updates = $this->client()->getUpdates();

        $this->assertCount(1, $updates);
        $this->assertSame('message_created', $updates[0]->updateType->value);
        $this->assertSame(7, $updates[0]->user->userId);
        $this->assertSame('hi', $updates[0]->message?->body?->text);
    }

    private function json(array $payload, int $status = 200, array $headers = []): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->factory->createResponse($status, '')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
