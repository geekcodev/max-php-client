<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum TextFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';
}
