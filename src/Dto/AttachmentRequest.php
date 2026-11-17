<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Dto;

use GeekCo\MaxPhpClient\Dto\AttachmentRequest\AudioAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\ContactAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\FileAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\InlineKeyboardAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\LocationAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\PhotoAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\ShareAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\StickerAttachmentRequest;
use GeekCo\MaxPhpClient\Dto\AttachmentRequest\VideoAttachmentRequest;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;
use GeekCo\MaxPhpClient\Internal\Json;

abstract readonly class AttachmentRequest
{
    public function __construct(
        public string $type,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $type = Json::requiredString($data, 'type');

        return match ($type) {
            'image' => PhotoAttachmentRequest::fromArray($data),
            'video' => VideoAttachmentRequest::fromArray($data),
            'audio' => AudioAttachmentRequest::fromArray($data),
            'file' => FileAttachmentRequest::fromArray($data),
            'sticker' => StickerAttachmentRequest::fromArray($data),
            'contact' => ContactAttachmentRequest::fromArray($data),
            'inline_keyboard' => InlineKeyboardAttachmentRequest::fromArray($data),
            'share' => ShareAttachmentRequest::fromArray($data),
            'location' => LocationAttachmentRequest::fromArray($data),
            default => throw new InvalidResponseException(sprintf('Unsupported attachment type "%s".', $type)),
        };
    }

    public function toArray(): array
    {
        return ['type' => $this->type];
    }
}
