<?php

namespace App\Policies;

use App\Models\EngageCampaign;
use App\Models\User;
use App\Support\Privileges;

class EngageCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('engage')
            || $user->hasPrivilege(Privileges::ENGAGE_VIEW)
            || $user->hasPrivilege(Privileges::ENGAGE_MANAGE);
    }

    public function view(User $user, EngageCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id
            && ($user->hasPrivilege(Privileges::ENGAGE_VIEW)
                || $user->hasPrivilege(Privileges::ENGAGE_MANAGE)
                || $user->canAccessModule('engage'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::ENGAGE_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, EngageCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id
            && ($user->hasPrivilege(Privileges::ENGAGE_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, EngageCampaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }
}
