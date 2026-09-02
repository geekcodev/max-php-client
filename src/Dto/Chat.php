<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Enum\ChatStatus;
use GeekCo\MaxPhpClient\Enum\ChatType;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

readonly class Chat
{
    /**
     * @param list<User>|null $participants
     */
    public function __construct(
        public int $chatId,
        public ChatType $type,
        public ChatStatus $status,
        public int $lastEventTime,
        public int $participantsCount,
        public bool $isPublic,
        public ?string $title = null,
        public ?Image $icon = null,
        public ?int $ownerId = null,
        public ?array $participants = null,
        public ?string $link = null,
        public ?string $description = null,
        public ?UserWithPhoto $dialogWithUser = null,
        public ?int $messagesCount = null,
        public ?Message $pinnedMessage = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $iconData = $data['icon'] ?? null;
        $dialogWithUserData = $data['dialog_with_user'] ?? null;
        $pinnedMessageData = $data['pinned_message'] ?? null;

        return new self(
            chatId: Json::requiredInt($data, 'chat_id'),
            type: Json::enum(ChatType::class, $data, 'type')
                ?? throw new InvalidResponseException('Field "type" must be a string.'),
            status: Json::enum(ChatStatus::class, $data, 'status')
                ?? throw new InvalidResponseException('Field "status" must be a string.'),
            lastEventTime: Json::requiredInt($data, 'last_event_time'),
            participantsCount: Json::requiredInt($data, 'participants_count'),
            isPublic: Json::requiredBool($data, 'is_public'),
            title: Json::string($data, 'title'),
            icon: \is_array($iconData) ? Image::fromArray($iconData) : null,
            ownerId: Json::int($data, 'owner_id'),
            participants: Json::map($data, 'participants', static fn (mixed $item): User => User::fromArray((array) $item)),
            link: Json::string($data, 'link'),
            description: Json::string($data, 'description'),
            dialogWithUser: \is_array($dialogWithUserData) ? UserWithPhoto::fromArray($dialogWithUserData) : null,
            messagesCount: Json::int($data, 'messages_count'),
            pinnedMessage: \is_array($pinnedMessageData) ? Message::fromArray($pinnedMessageData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'chat_id' => $this->chatId,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'last_event_time' => $this->lastEventTime,
            'participants_count' => $this->participantsCount,
            'is_public' => $this->isPublic,
            'title' => $this->title,
            'icon' => $this->icon?->toArray(),
            'owner_id' => $this->ownerId,
            'participants' => $this->participants === null
                ? null
                : array_map(static fn (User $user): array => $user->toArray(), $this->participants),
            'link' => $this->link,
            'description' => $this->description,
            'dialog_with_user' => $this->dialogWithUser?->toArray(),
            'messages_count' => $this->messagesCount,
            'pinned_message' => $this->pinnedMessage?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
