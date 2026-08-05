<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum SenderAction: string
{
    case TypingOn = 'typing_on';
    case SendingPhoto = 'sending_photo';
    case SendingVideo = 'sending_video';
    case SendingAudio = 'sending_audio';
    case SendingFile = 'sending_file';
}
