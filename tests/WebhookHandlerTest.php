<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Webhook\WebhookHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class WebhookHandlerTest extends TestCase
{
    #[Test]
    public function it_decodes_a_single_update(): void
    {
        $handler = new WebhookHandler('my-secret');
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withHeader('X-Max-Bot-Api-Secret', 'my-secret')
            ->withBody((new HttpFactory())->createStream(json_encode([
                'update_type' => 'bot_started',
                'timestamp' => 1,
                'chat_id' => 1,
                'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 1],
            ], JSON_THROW_ON_ERROR)));

        $update = $handler->decode($request);

        $this->assertSame('bot_started', $update->updateType->value);
    }

    #[Test]
    public function it_rejects_requests_with_a_wrong_secret(): void
    {
        $handler = new WebhookHandler('my-secret');
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withHeader('X-Max-Bot-Api-Secret', 'wrong')
            ->withBody((new HttpFactory())->createStream('{}'));

        $this->expectException(InvalidResponseException::class);

        $handler->decode($request);
    }

    #[Test]
    public function it_accepts_requests_without_secret_when_not_configured(): void
    {
        $handler = new WebhookHandler();
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withBody((new HttpFactory())->createStream(json_encode([
                'update_type' => 'bot_started',
                'timestamp' => 1,
                'chat_id' => 1,
                'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 1],
            ], JSON_THROW_ON_ERROR)));

        $update = $handler->decode($request);

        $this->assertInstanceOf(\GeekCo\MaxPhpClient\Dto\Update::class, $update);
    }

    #[Test]
    public function it_decodes_a_list_of_updates(): void
    {
        $handler = new WebhookHandler();
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withBody((new HttpFactory())->createStream(json_encode([
                ['update_type' => 'bot_started', 'timestamp' => 1, 'chat_id' => 1, 'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 1]],
                ['update_type' => 'bot_started', 'timestamp' => 2, 'chat_id' => 1, 'user' => ['user_id' => 2, 'first_name' => 'A', 'is_bot' => false, 'last_activity_time' => 1]],
            ], JSON_THROW_ON_ERROR)));

        $updates = $handler->decode($request);

        $this->assertIsArray($updates);
        $this->assertCount(2, $updates);
    }

    #[Test]
    public function it_rejects_a_non_array_webhook_payload(): void
    {
        $handler = new WebhookHandler();
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withBody((new HttpFactory())->createStream('"just-a-string"'));

        $this->expectException(InvalidResponseException::class);

        $handler->decode($request);
    }

    #[Test]
    public function it_rejects_invalid_json_in_the_webhook_body(): void
    {
        $handler = new WebhookHandler();
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withBody((new HttpFactory())->createStream('{broken'));

        $this->expectException(InvalidResponseException::class);

        $handler->decode($request);
    }

    #[Test]
    public function it_rejects_a_non_object_update_in_a_list(): void
    {
        $handler = new WebhookHandler();
        $request = (new HttpFactory())->createRequest('POST', 'https://example.com/hook')
            ->withBody((new HttpFactory())->createStream('[1, 2]'));

        $this->expectException(InvalidResponseException::class);

        $handler->decode($request);
    }
}
