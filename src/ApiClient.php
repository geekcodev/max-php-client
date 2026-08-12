<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient;

use GeekCo\MaxPhpClient\Dto\AddChatMembersResult;
use GeekCo\MaxPhpClient\Dto\BotCommand;
use GeekCo\MaxPhpClient\Dto\BotInfo;
use GeekCo\MaxPhpClient\Dto\Chat;
use GeekCo\MaxPhpClient\Dto\ChatAdminsResult;
use GeekCo\MaxPhpClient\Dto\ChatListResult;
use GeekCo\MaxPhpClient\Dto\ChatMember;
use GeekCo\MaxPhpClient\Dto\ChatMembersResult;
use GeekCo\MaxPhpClient\Dto\EditChatBody;
use GeekCo\MaxPhpClient\Dto\Message;
use GeekCo\MaxPhpClient\Dto\NewMessageBody;
use GeekCo\MaxPhpClient\Dto\PinMessageBody;
use GeekCo\MaxPhpClient\Dto\Recipient;
use GeekCo\MaxPhpClient\Dto\Subscription;
use GeekCo\MaxPhpClient\Dto\SuccessResponse;
use GeekCo\MaxPhpClient\Dto\Update;
use GeekCo\MaxPhpClient\Dto\UploadResult;
use GeekCo\MaxPhpClient\Dto\VideoInfo;
use GeekCo\MaxPhpClient\Enum\ChatAdminPermission;
use GeekCo\MaxPhpClient\Enum\SenderAction;
use GeekCo\MaxPhpClient\Enum\UploadType;
use GeekCo\MaxPhpClient\Exception\InvalidArgumentException;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\RateLimit\RateLimiter;
use GeekCo\MaxPhpClient\Retry\RetryStrategy;
use GeekCo\MaxPhpClient\Transport\HttpClient;
use GeekCo\MaxPhpClient\Transport\RequestBuilder;
use GeekCo\MaxPhpClient\Transport\ResponseDecoder;
use GeekCo\MaxPhpClient\Upload\Uploader;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class ApiClient
{
    public const BASE_URI = 'https://platform-api2.max.ru';

    private const MAX_BOT_COMMANDS = 32;
    private const MAX_MEMBERS_PER_REQUEST = 100;

    public function __construct(
        private readonly HttpClient $http,
        private readonly ?RateLimiter $rateLimiter = null,
        private readonly ?Uploader $uploader = null,
    ) {
    }

    public static function create(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        UriFactoryInterface $uriFactory,
        string $accessToken,
        string $baseUri = self::BASE_URI,
        ?RetryStrategy $retryStrategy = null,
        ?RateLimiter $rateLimiter = null,
    ): self {
        $requestBuilder = new RequestBuilder($uriFactory, $requestFactory, $streamFactory, $baseUri, $accessToken);
        $http = new HttpClient($httpClient, $requestBuilder, new ResponseDecoder(), $retryStrategy ?? new RetryStrategy());

        return new self($http, $rateLimiter ?? new RateLimiter(), new Uploader($http, $streamFactory));
    }

    public function getMe(): BotInfo
    {
        return BotInfo::fromArray($this->requestObject('GET', '/me'));
    }

    /**
     * @param list<BotCommand> $commands
     */
    public function editBotCommands(array $commands): SuccessResponse
    {
        if (count($commands) > self::MAX_BOT_COMMANDS) {
            throw new InvalidArgumentException(
                sprintf('A bot can have no more than %d commands.', self::MAX_BOT_COMMANDS),
            );
        }

        $body = ['commands' => array_map(static fn (BotCommand $command): array => $command->toArray(), $commands)];

        return SuccessResponse::fromArray($this->requestObject('PATCH', '/me/commands', jsonBody: $body));
    }

    /**
     * @deprecated Use webhook subscriptions and store chat_id locally instead.
     */
    public function getChats(?int $marker = null, ?int $count = null): ChatListResult
    {
        $query = $this->query(['marker' => $marker, 'count' => $count]);

        return ChatListResult::fromArray($this->requestObject('GET', '/chats', $query));
    }

    public function getChat(int $chatId): Chat
    {
        return Chat::fromArray($this->requestObject('GET', sprintf('/chats/%d', $chatId)));
    }

    public function editChat(int $chatId, EditChatBody $body): Chat
    {
        $this->acquire($chatId);

        return Chat::fromArray($this->requestObject('PATCH', sprintf('/chats/%d', $chatId), jsonBody: $body->toArray()));
    }

    public function sendBotAction(int $chatId, SenderAction $action): SuccessResponse
    {
        $this->acquire($chatId);

        return SuccessResponse::fromArray(
            $this->requestObject('POST', sprintf('/chats/%d/actions', $chatId), jsonBody: ['type' => $action->value]),
        );
    }

    public function getPinnedMessage(int $chatId): ?Message
    {
        $data = $this->request('GET', sprintf('/chats/%d/pin', $chatId));
        if ($data === null) {
            return null;
        }

        return Message::fromArray($this->object($data, 'pin'));
    }

    public function pinMessage(int $chatId, PinMessageBody $body): SuccessResponse
    {
        $this->acquire($chatId);

        return SuccessResponse::fromArray(
            $this->requestObject('PUT', sprintf('/chats/%d/pin', $chatId), jsonBody: $body->toArray()),
        );
    }

    public function unpinMessage(int $chatId): SuccessResponse
    {
        $this->acquire($chatId);

        return SuccessResponse::fromArray($this->requestObject('DELETE', sprintf('/chats/%d/pin', $chatId)));
    }

    public function getBotMembership(int $chatId): ChatMember
    {
        return ChatMember::fromArray($this->requestObject('GET', sprintf('/chats/%d/members/me', $chatId)));
    }

    public function removeBotFromChat(int $chatId): SuccessResponse
    {
        $this->acquire($chatId);

        return SuccessResponse::fromArray($this->requestObject('DELETE', sprintf('/chats/%d/members/me', $chatId)));
    }

    public function getChatAdmins(int $chatId, ?int $marker = null, ?int $count = null): ChatAdminsResult
    {
        $query = $this->query(['marker' => $marker, 'count' => $count]);

        return ChatAdminsResult::fromArray(
            $this->requestObject('GET', sprintf('/chats/%d/members/admins', $chatId), $query),
        );
    }

    /**
     * @param list<ChatAdminPermission> $permissions
     */
    public function addChatAdmin(
        int $chatId,
        int $userId,
        array $permissions,
        ?string $alias = null,
    ): SuccessResponse {
        $this->acquire($chatId);

        $body = [
            'user_id' => $userId,
            'permissions' => array_map(static fn (ChatAdminPermission $permission): string => $permission->value, $permissions),
        ];
        if ($alias !== null) {
            $body['alias'] = $alias;
        }

        return SuccessResponse::fromArray(
            $this->requestObject('POST', sprintf('/chats/%d/members/admins', $chatId), jsonBody: $body),
        );
    }

    public function removeChatAdmin(int $chatId, int $userId): SuccessResponse
    {
        $this->acquire($chatId);

        return SuccessResponse::fromArray(
            $this->requestObject('DELETE', sprintf('/chats/%d/members/admins/%d', $chatId, $userId)),
        );
    }

    /**
     * @param list<int>|null $userIds
     */
    public function getChatMembers(
        int $chatId,
        ?array $userIds = null,
        ?int $marker = null,
        ?int $count = null,
    ): ChatMembersResult {
        if ($count !== null && ($count < 1 || $count > 100)) {
            throw new InvalidArgumentException('count must be between 1 and 100.');
        }

        $query = $this->query([
            'user_ids' => $userIds !== null ? implode(',', $userIds) : null,
            'marker' => $marker,
            'count' => $count,
        ]);

        return ChatMembersResult::fromArray(
            $this->requestObject('GET', sprintf('/chats/%d/members', $chatId), $query),
        );
    }

    /**
     * @param list<int> $userIds
     */
    public function addChatMembers(int $chatId, array $userIds): AddChatMembersResult
    {
        if ($userIds === []) {
            throw new InvalidArgumentException('user_ids must not be empty.');
        }
        if (count($userIds) > self::MAX_MEMBERS_PER_REQUEST) {
            throw new InvalidArgumentException(
                sprintf('No more than %d users per request.', self::MAX_MEMBERS_PER_REQUEST),
            );
        }

        $this->acquire($chatId);

        return AddChatMembersResult::fromArray(
            $this->requestObject('POST', sprintf('/chats/%d/members', $chatId), jsonBody: ['user_ids' => $userIds]),
        );
    }

    public function removeChatMember(int $chatId, int $userId, bool $block = false): SuccessResponse
    {
        $this->acquire($chatId);

        $query = $this->query(['user_id' => $userId, 'block' => $block]);

        return SuccessResponse::fromArray($this->requestObject('DELETE', sprintf('/chats/%d/members', $chatId), $query));
    }

    /**
     * @return list<Subscription>
     */
    public function getSubscriptions(): array
    {
        return $this->listFromField(
            $this->request('GET', '/subscriptions'),
            'subscriptions',
            static fn (array $item): Subscription => Subscription::fromArray($item),
        );
    }

    /**
     * @param list<string>|null $updateTypes
     */
    public function createSubscription(string $url, ?array $updateTypes = null, ?string $secret = null): SuccessResponse
    {
        if ($secret !== null && !preg_match('/^[a-zA-Z0-9_-]{5,256}$/', $secret)) {
            throw new InvalidArgumentException(
                'secret must be 5-256 characters of [a-zA-Z0-9_-].',
            );
        }

        $body = ['url' => $url];
        if ($updateTypes !== null) {
            $body['update_types'] = $updateTypes;
        }
        if ($secret !== null) {
            $body['secret'] = $secret;
        }

        return SuccessResponse::fromArray($this->requestObject('POST', '/subscriptions', jsonBody: $body));
    }

    public function deleteSubscription(string $url): SuccessResponse
    {
        $query = $this->query(['url' => $url]);

        return SuccessResponse::fromArray($this->requestObject('DELETE', '/subscriptions', $query));
    }

    /**
     * @param list<string>|null $types
     *
     * @return list<Update>
     */
    public function getUpdates(?int $limit = null, ?int $timeout = null, ?int $marker = null, ?array $types = null): array
    {
        if ($limit !== null && ($limit < 1 || $limit > 1000)) {
            throw new InvalidArgumentException('limit must be between 1 and 1000.');
        }
        if ($timeout !== null && ($timeout < 0 || $timeout > 90)) {
            throw new InvalidArgumentException('timeout must be between 0 and 90 seconds.');
        }

        $query = $this->query([
            'limit' => $limit,
            'timeout' => $timeout,
            'marker' => $marker,
            'types' => $types !== null ? implode(',', $types) : null,
        ]);

        return $this->listFromField(
            $this->request('GET', '/updates', $query),
            'updates',
            static fn (array $item): Update => Update::fromArray($item),
        );
    }

    /**
     * @param list<int>|null $messageIds
     *
     * @return list<Message>
     */
    public function getMessages(
        ?int $chatId = null,
        ?array $messageIds = null,
        ?int $from = null,
        ?int $to = null,
        ?int $count = null,
    ): array {
        if ($count !== null && ($count < 1 || $count > 100)) {
            throw new InvalidArgumentException('count must be between 1 and 100.');
        }

        $query = $this->query([
            'chat_id' => $chatId,
            'message_ids' => $messageIds !== null ? implode(',', $messageIds) : null,
            'from' => $from,
            'to' => $to,
            'count' => $count,
        ]);

        return $this->listFromField(
            $this->request('GET', '/messages', $query),
            'messages',
            static fn (array $item): Message => Message::fromArray($item),
        );
    }

    public function sendMessage(Recipient $recipient, NewMessageBody $body, ?bool $disableLinkPreview = null): Message
    {
        if ($recipient->chatId === null && $recipient->userId === null) {
            throw new InvalidArgumentException('Either chat_id or user_id must be provided.');
        }

        $query = $this->query([
            'chat_id' => $recipient->chatId,
            'user_id' => $recipient->userId,
            'disable_link_preview' => $disableLinkPreview,
        ]);

        if ($recipient->chatId !== null) {
            $this->acquire($recipient->chatId);
        }

        return Message::fromArray($this->requestObject('POST', '/messages', $query, $body->toArray()));
    }

    public function editMessage(string $messageId, NewMessageBody $body): Message
    {
        $query = $this->query(['message_id' => $messageId]);

        return Message::fromArray($this->requestObject('PUT', '/messages', $query, $body->toArray()));
    }

    public function deleteMessage(string $messageId): SuccessResponse
    {
        $query = $this->query(['message_id' => $messageId]);

        return SuccessResponse::fromArray($this->requestObject('DELETE', '/messages', $query));
    }

    public function getMessageById(string $messageId): Message
    {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $messageId)) {
            throw new InvalidArgumentException('message_id must match [a-zA-Z0-9_-]+.');
        }

        return Message::fromArray($this->requestObject('GET', sprintf('/messages/%s', $messageId)));
    }

    public function getVideoInfo(string $videoToken): VideoInfo
    {
        return VideoInfo::fromArray($this->requestObject('GET', sprintf('/videos/%s', $videoToken)));
    }

    public function uploadMedia(UploadType $type, string $filePath): UploadResult
    {
        if ($this->uploader === null) {
            throw new \LogicException('Upload support is not enabled for this client instance.');
        }

        return $this->uploader->upload($type, $filePath);
    }

    public function sendAnswer(string $callbackId, ?NewMessageBody $message = null): SuccessResponse
    {
        $query = $this->query(['callback_id' => $callbackId]);
        $body = $message === null ? null : ['message' => $message->toArray()];

        return SuccessResponse::fromArray($this->requestObject('POST', '/answers', $query, $body));
    }

    /**
     * @param array<string, int|string|bool|float|null> $values
     *
     * @return array<string, int|string|bool|float>
     */
    private function query(array $values): array
    {
        $query = [];
        foreach ($values as $key => $value) {
            if ($value !== null) {
                $query[$key] = $value;
            }
        }

        return $query;
    }

    private function acquire(int $chatId): void
    {
        $this->rateLimiter?->acquire($chatId);
    }

    /**
     * @param array<string, int|string|bool|float> $query
     * @param array<mixed>|null $jsonBody
     */
    private function request(
        string $method,
        string $path,
        array $query = [],
        ?array $jsonBody = null,
    ): mixed {
        return $this->http->request($method, $path, $query, $jsonBody);
    }

    /**
     * @param array<string, int|string|bool|float> $query
     * @param array<mixed>|null $jsonBody
     *
     * @return array<mixed>
     */
    private function requestObject(string $method, string $path, array $query = [], ?array $jsonBody = null): array
    {
        $data = $this->request($method, $path, $query, $jsonBody);
        if (!\is_array($data)) {
            throw new InvalidResponseException('Expected a JSON object in the response.');
        }

        return $data;
    }

    /**
     * @template T
     *
     * @param callable(array<mixed>): T $mapper
     *
     * @return list<T>
     */
    private function listFromField(mixed $data, string $field, callable $mapper): array
    {
        if (!\is_array($data) || !isset($data[$field]) || !\is_array($data[$field])) {
            throw new InvalidResponseException(sprintf('Expected a JSON list in response field "%s".', $field));
        }

        $items = [];
        foreach ($data[$field] as $item) {
            if (!\is_array($item)) {
                throw new InvalidResponseException(sprintf('Expected a list of JSON objects in response field "%s".', $field));
            }
            $items[] = $mapper($item);
        }

        return $items;
    }

    /**
     * @return array<mixed>
     */
    private function object(mixed $data, string $field): array
    {
        if (\is_array($data) && isset($data[$field]) && \is_array($data[$field])) {
            return $data[$field];
        }

        throw new InvalidResponseException(sprintf('Expected an object in response field "%s".', $field));
    }
}
