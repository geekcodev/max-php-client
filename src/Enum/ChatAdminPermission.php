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

    /**
     * @deprecated Returned by the API for legacy permissions; must not be granted.
     */
    case PostEditDeleteMessage = 'post_edit_delete_message';

    /**
     * @deprecated Returned by the API for legacy permissions; must not be granted.
     */
    case EditMessage = 'edit_message';

    /**
     * @deprecated Returned by the API for legacy permissions; must not be granted.
     */
    case DeleteMessage = 'delete_message';

    public function isDeprecated(): bool
    {
        return in_array($this, self::deprecated(), true);
    }

    /**
     * @return list<self>
     */
    private static function deprecated(): array
    {
        return [self::PostEditDeleteMessage, self::EditMessage, self::DeleteMessage];
    }
}
