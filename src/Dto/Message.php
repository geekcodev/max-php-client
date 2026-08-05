<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Internal\Json;

readonly class Message
{
    public function __construct(
        public ?User $sender,
        public Recipient $recipient,
        public int $timestamp,
        public ?LinkedMessage $link = null,
        public ?MessageBody $body = null,
        public ?MessageStat $stat = null,
        public ?string $url = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $recipientData = $data['recipient'] ?? null;
        if (!\is_array($recipientData)) {
            throw new \GeekCo\MaxPhpClient\Exception\InvalidResponseException('Field "recipient" must be an object.');
        }

        $senderData = $data['sender'] ?? null;
        $linkData = $data['link'] ?? null;
        $bodyData = $data['body'] ?? null;
        $statData = $data['stat'] ?? null;

        return new self(
            sender: \is_array($senderData) ? User::fromArray($senderData) : null,
            recipient: Recipient::fromArray($recipientData),
            timestamp: Json::requiredInt($data, 'timestamp'),
            link: \is_array($linkData) ? LinkedMessage::fromArray($linkData) : null,
            body: \is_array($bodyData) ? MessageBody::fromArray($bodyData) : null,
            stat: \is_array($statData) ? MessageStat::fromArray($statData) : null,
            url: Json::string($data, 'url'),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'sender' => $this->sender?->toArray(),
            'recipient' => $this->recipient->toArray(),
            'timestamp' => $this->timestamp,
            'link' => $this->link?->toArray(),
            'body' => $this->body?->toArray(),
            'stat' => $this->stat?->toArray(),
            'url' => $this->url,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
