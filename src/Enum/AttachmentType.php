<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum AttachmentType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';
    case Sticker = 'sticker';
    case InlineKeyboard = 'inline_keyboard';
    case Location = 'location';
    case Share = 'share';
}
