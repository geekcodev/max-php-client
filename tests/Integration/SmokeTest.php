<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests\Integration;

use GeekCo\MaxPhpClient\ApiClient;
use GeekCo\MaxPhpClient\Dto\BotInfo;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\Subscription;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Exception\NetworkException;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[RequiresEnvironmentVariable('MAX_API_TOKEN')]
final class SmokeTest extends TestCase
{
    private ApiClient $client;

    protected function setUp(): void
    {
        $token = getenv('MAX_API_TOKEN');
        if (!is_string($token) || $token === '') {
            $this->markTestSkipped('MAX_API_TOKEN is not set.');
        }

        $factory = new HttpFactory();
        $options = ['connect_timeout' => 10, 'timeout' => 15];

        $caFile = __DIR__ . '/../Fixtures/max-ca-chain.pem';
        if (is_file($caFile)) {
            $options['verify'] = $caFile;
        }

        $this->client = ApiClient::create(
            httpClient: new Client($options),
            requestFactory: $factory,
            streamFactory: $factory,
            uriFactory: $factory,
            accessToken: $token,
            retryStrategy: new RetryStrategy(maxAttempts: 1),
        );
    }

    #[Test]
    public function it_fetches_bot_info(): void
    {
        $me = $this->probe(fn (): BotInfo => $this->client->getMe());

        $this->assertSame(true, $me->isBot);
        $this->assertNotSame('', $me->username);
    }

    #[Test]
    public function it_lists_webhook_subscriptions(): void
    {
        $subscriptions = $this->probe(fn (): array => $this->client->getSubscriptions());

        $this->assertContainsOnlyInstancesOf(Subscription::class, $subscriptions);
    }

    #[Test]
    public function it_polls_updates(): void
    {
        $updates = $this->probe(fn (): array => $this->client->getUpdates(limit: 10, timeout: 0));

        $this->assertContainsOnlyInstancesOf(Update::class, $updates);
    }

    #[Test]
    public function it_reads_messages_of_the_last_dialog(): void
    {
        $updates = $this->probe(fn (): array => $this->client->getUpdates(limit: 1, timeout: 0));
        if ($updates === []) {
            $this->markTestSkipped('No updates available to derive a chat_id.');
        }

        $chatId = $updates[0]->chatId;
        if ($chatId === null) {
            $this->markTestSkipped('Update does not carry a chat_id.');
        }

        $messages = $this->probe(fn (): array => $this->client->getMessages(chatId: $chatId, count: 5));

        $this->assertContainsOnlyInstancesOf(Message::class, $messages);
    }

    /**
     * @template T
     *
     * @param callable(): T $callable
     *
     * @return T
     */
    private function probe(callable $callable): mixed
    {
        try {
            return $callable();
        } catch (NetworkException $e) {
            $this->markTestSkipped('MAX API unreachable from test environment: ' . $e->getMessage());

            throw $e;
        }
    }
}
