<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum UploadType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case File = 'file';
}
