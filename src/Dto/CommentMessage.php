<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

/**
 * Комментарий к посту в канале. В отличие от `Message`, у комментария нет
 * публичного `url`, а в теле нет вложений. `sender` бывает `null`, если комментарий
 * опубликован от имени канала.
 */
readonly class CommentMessage
{
    public function __construct(
        public Recipient $recipient,
        public int $timestamp,
        public CommentMessageBody $body,
        public ?User $sender = null,
        public ?CommentLinkedMessage $link = null,
        public ?MessageStat $stat = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $recipientData = $data['recipient'] ?? null;
        if (!\is_array($recipientData)) {
            throw new InvalidResponseException('Field "recipient" must be an object.');
        }

        $senderData = $data['sender'] ?? null;
        $linkData = $data['link'] ?? null;
        $bodyData = $data['body'] ?? null;
        if (!\is_array($bodyData)) {
            throw new InvalidResponseException('Field "body" must be an object.');
        }

        $statData = $data['stat'] ?? null;

        return new self(
            recipient: Recipient::fromArray($recipientData),
            timestamp: Json::requiredInt($data, 'timestamp'),
            body: CommentMessageBody::fromArray($bodyData),
            sender: \is_array($senderData) ? User::fromArray($senderData) : null,
            link: \is_array($linkData) ? CommentLinkedMessage::fromArray($linkData) : null,
            stat: \is_array($statData) ? MessageStat::fromArray($statData) : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'sender' => $this->sender?->toArray(),
            'recipient' => $this->recipient->toArray(),
            'timestamp' => $this->timestamp,
            'link' => $this->link?->toArray(),
            'body' => $this->body->toArray(),
            'stat' => $this->stat?->toArray(),
        ], static fn (mixed $value): bool => $value !== null);
    }
}
