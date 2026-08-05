<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Webhook;

use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use Psr\Http\Message\RequestInterface;

final class WebhookHandler
{
    private const SECRET_HEADER = 'X-Max-Bot-Api-Secret';

    public function __construct(
        private readonly ?string $secret = null,
    ) {
    }

    public function verify(RequestInterface $request): bool
    {
        if ($this->secret === null) {
            return true;
        }

        return hash_equals($this->secret, $request->getHeaderLine(self::SECRET_HEADER));
    }

    /**
     * @return Update|list<Update>
     */
    public function decode(RequestInterface $request): Update|array
    {
        if (!$this->verify($request)) {
            throw new InvalidResponseException('Invalid webhook secret header.');
        }

        $body = (string) $request->getBody();
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidResponseException('Invalid JSON in webhook body.', previous: $e);
        }

        if (!\is_array($decoded)) {
            throw new InvalidResponseException('Invalid webhook payload.');
        }

        if (array_is_list($decoded)) {
            $updates = [];
            foreach ($decoded as $item) {
                if (!\is_array($item)) {
                    throw new InvalidResponseException('Invalid webhook payload item.');
                }
                $updates[] = Update::fromArray($item);
            }

            return $updates;
        }

        return Update::fromArray($decoded);
    }
}
