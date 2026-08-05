<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum ChatStatus: string
{
    case Active = 'active';
    case Removed = 'removed';
    case Left = 'left';
    case Closed = 'closed';
}
