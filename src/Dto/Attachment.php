<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Dto\Attachment\AudioAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\ContactAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\FileAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\InlineKeyboardAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\LocationAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\PhotoAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\ShareAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\StickerAttachment;
use GeekCo\MaxPhpClient\Dto\Attachment\VideoAttachment;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

abstract readonly class Attachment
{
    public function __construct(
        public string $type,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $type = Json::requiredString($data, 'type');

        return match ($type) {
            'image' => PhotoAttachment::fromArray($data),
            'video' => VideoAttachment::fromArray($data),
            'audio' => AudioAttachment::fromArray($data),
            'file' => FileAttachment::fromArray($data),
            'sticker' => StickerAttachment::fromArray($data),
            'contact' => ContactAttachment::fromArray($data),
            'inline_keyboard' => InlineKeyboardAttachment::fromArray($data),
            'share' => ShareAttachment::fromArray($data),
            'location' => LocationAttachment::fromArray($data),
            default => throw new InvalidResponseException(sprintf('Unsupported attachment type "%s".', $type)),
        };
    }

    public function toArray(): array
    {
        return ['type' => $this->type];
    }
}
