<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\Privileges;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('email')
            || $user->hasPrivilege(Privileges::EMAIL_VIEW)
            || $user->hasPrivilege(Privileges::EMAIL_MANAGE);
    }

    public function view(User $user, EmailTemplate $template): bool
    {
        return $user->tenant_id === $template->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_VIEW)
                || $user->hasPrivilege(Privileges::EMAIL_MANAGE)
                || $user->canAccessModule('email'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, EmailTemplate $template): bool
    {
        return $user->tenant_id === $template->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, EmailTemplate $template): bool
    {
        return $user->tenant_id === $template->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }
}
