<?php

namespace App\Policies;

use App\Models\EmailList;
use App\Models\User;
use App\Support\Privileges;

class EmailListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('email')
            || $user->hasPrivilege(Privileges::EMAIL_VIEW)
            || $user->hasPrivilege(Privileges::EMAIL_MANAGE);
    }

    public function view(User $user, EmailList $list): bool
    {
        return $user->tenant_id === $list->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_VIEW)
                || $user->hasPrivilege(Privileges::EMAIL_MANAGE)
                || $user->canAccessModule('email'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, EmailList $list): bool
    {
        return $user->tenant_id === $list->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, EmailList $list): bool
    {
        return $user->tenant_id === $list->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }
}
