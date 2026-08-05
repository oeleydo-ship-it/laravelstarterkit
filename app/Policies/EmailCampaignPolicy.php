<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\User;
use App\Support\Privileges;

class EmailCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('email')
            || $user->hasPrivilege(Privileges::EMAIL_VIEW)
            || $user->hasPrivilege(Privileges::EMAIL_MANAGE);
    }

    public function view(User $user, EmailCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_VIEW)
                || $user->hasPrivilege(Privileges::EMAIL_MANAGE)
                || $user->canAccessModule('email'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, EmailCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, EmailCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id
            && ($user->hasPrivilege(Privileges::EMAIL_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function send(User $user, EmailCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }
}
