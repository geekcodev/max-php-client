<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Пересланное или ответное сообщение.
 *
 * Спецификация отдаёт `sender` объектом `User`, а идентификаторы — в `message.mid`
 * и `chat_id`, но прод отдаёт `sender` числом или числовой строкой, а `mid`
 * на верхнем уровне (см. `docs/api-reference.md`, раздел 9). Поэтому класс
 * принимает обе формы: `senderUser` и `chatId` — по спеке, `sender`, `mid` и
 * `chat` — по фактическим ответам API.
 */
readonly class LinkedMessage
{
    /**
     * @param int|null         $sender     Идентификатор отправителя, если API прислал число или строку.
     * @param string|null      $chat       @deprecated Устаревшее поле: используйте `chatId`.
     */
    public function __construct(
        public string $type,
        public ?int $sender,
        public string $mid,
        public ?string $chat = null,
        public ?MessageBody $message = null,
        public ?User $senderUser = null,
        public ?int $chatId = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? null;
        $message = \is_array($messageData) ? MessageBody::fromArray($messageData) : null;

        $senderData = $data['sender'] ?? null;
        $senderUser = \is_array($senderData) ? User::fromArray($senderData) : null;

        $chatId = Json::tolerantInt($data, 'chat_id');
        $mid = Json::string($data, 'mid') ?? $message->mid
            ?? throw new InvalidResponseException('Field "mid" must be a string.');

        return new self(
            type: Json::requiredString($data, 'type'),
            sender: $senderUser->userId ?? Json::tolerantInt($data, 'sender'),
            mid: $mid,
            chat: Json::string($data, 'chat') ?? ($chatId === null ? null : (string) $chatId),
            message: $message,
            senderUser: $senderUser,
            chatId: $chatId,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'mid' => $this->mid,
            'sender' => $this->senderUser?->toArray() ?? $this->sender,
            'message' => $this->message?->toArray(),
            'chat_id' => $this->chatId,
            'chat' => $this->chat,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
