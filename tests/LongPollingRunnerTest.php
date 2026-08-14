<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\LongPolling\LongPollingRunner;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Tests\Support\MockHttpClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LongPollingRunnerTest extends TestCase
{
    #[Test]
    public function it_forwards_updates_and_advances_the_marker(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = ApiClient::create(
            $http,
            $factory,
            $factory,
            $factory,
            'token',
            retryStrategy: new RetryStrategy(baseDelaySeconds: 0.01),
        );

        $http->next(fn ($request) => $this->response($factory, ['updates' => [[
            'update_type' => 'message_created',
            'timestamp' => 10,
            'chat_id' => 1,
            'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 10],
        ]], 'marker' => 50]));
        $http->next(fn ($request) => $this->response($factory, ['updates' => [[
            'update_type' => 'message_created',
            'timestamp' => 20,
            'chat_id' => 1,
            'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 10],
        ]], 'marker' => 60]));
        ;

        $seen = [];
        $runner = new LongPollingRunner($client, static function ($update) use (&$seen): bool {
            $seen[] = $update->timestamp;

            return count($seen) < 2;
        });

        $marker = $runner->run();

        $this->assertSame([10, 20], $seen);
        $this->assertSame(60, $marker);
    }

    #[Test]
    public function it_throws_when_a_poll_fails_with_break_on_failure_enabled(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = ApiClient::create(
            $http,
            $factory,
            $factory,
            $factory,
            'token',
            retryStrategy: new RetryStrategy(maxAttempts: 1, baseDelaySeconds: 0.01),
        );

        $http->next(fn ($request) => $factory->createResponse(503, 'Service unavailable'));

        $runner = new LongPollingRunner($client, static fn () => true);

        $this->expectException(\GeekCo\MaxPhpClient\Exception\ApiException::class);

        $runner->run();
    }

    #[Test]
    public function it_continues_after_a_failure_when_not_breaking(): void
    {
        $factory = new HttpFactory();
        $http = new MockHttpClient();
        $client = ApiClient::create(
            $http,
            $factory,
            $factory,
            $factory,
            'token',
            retryStrategy: new RetryStrategy(maxAttempts: 1, baseDelaySeconds: 0.01),
        );

        $http->next(fn ($request) => $factory->createResponse(503, 'Service unavailable'));
        $http->next(fn ($request) => $this->response($factory, ['updates' => [[
            'update_type' => 'message_created',
            'timestamp' => 30,
            'chat_id' => 1,
            'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 10],
        ]], 'marker' => 30]));

        $seen = [];
        $runner = new LongPollingRunner(
            $client,
            static function ($update) use (&$seen): bool {
                $seen[] = $update->timestamp;

                return false;
            },
            breakOnFailure: false,
        );

        $marker = $runner->run();

        $this->assertSame([30], $seen);
        $this->assertSame(30, $marker);
    }

    private function response(HttpFactory $factory, array $payload): \Psr\Http\Message\ResponseInterface
    {
        return $factory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
    }
}
