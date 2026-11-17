<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum MessageLinkType: string
{
    case Forward = 'forward';
    case Reply = 'reply';
}
