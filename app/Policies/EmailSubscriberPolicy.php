<?php

namespace App\Policies;

use App\Models\EmailSubscriber;
use App\Models\User;
use App\Support\Privileges;

class EmailSubscriberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('email')
            || $user->hasPrivilege(Privileges::EMAIL_VIEW)
            || $user->hasPrivilege(Privileges::EMAIL_MANAGE);
    }

    public function view(User $user, EmailSubscriber $subscriber): bool
    {
        return $user->tenant_id === $subscriber->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_VIEW)
                || $user->hasPrivilege(Privileges::EMAIL_MANAGE)
                || $user->canAccessModule('email'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, EmailSubscriber $subscriber): bool
    {
        return $user->tenant_id === $subscriber->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, EmailSubscriber $subscriber): bool
    {
        return $user->tenant_id === $subscriber->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }
}
