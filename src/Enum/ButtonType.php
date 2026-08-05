<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum ButtonType: string
{
    case Callback = 'callback';
    case Link = 'link';
    case RequestContact = 'request_contact';
    case RequestGeoLocation = 'request_geo_location';
    case OpenApp = 'open_app';
    case Message = 'message';
    case Clipboard = 'clipboard';
}
