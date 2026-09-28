<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum Markup: string
{
    case Strong = 'strong';
    case Emphasized = 'emphasized';
    case Underline = 'underline';
    case Strikethrough = 'strikethrough';
    case Monospaced = 'monospaced';
    case Highlighted = 'highlighted';
    case Link = 'link';
    case Quote = 'quote';
    case Heading = 'heading';
    case UserMention = 'user_mention';
}
