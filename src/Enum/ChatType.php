<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum ChatType: string
{
    case Chat = 'chat';
    case Channel = 'channel';
    case Dialog = 'dialog';
}
