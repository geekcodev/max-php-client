<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Enum;

enum ChatAdminPermission: string
{
    case ReadAllMessages = 'read_all_messages';
    case AddRemoveMembers = 'add_remove_members';
    case AddAdmins = 'add_admins';
    case ChangeChatInfo = 'change_chat_info';
    case PinMessage = 'pin_message';
    case Write = 'write';
    case CanCall = 'can_call';
    case EditLink = 'edit_link';
    case Edit = 'edit';
    case Delete = 'delete';
    case ViewStats = 'view_stats';
}
