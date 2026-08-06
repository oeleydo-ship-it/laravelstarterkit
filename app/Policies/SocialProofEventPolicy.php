<?php

namespace App\Policies;

use App\Models\SocialProofEvent;
use App\Models\User;
use App\Support\Privileges;

class SocialProofEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('socialproof')
            || $user->hasPrivilege(Privileges::SOCIALPROOF_VIEW)
            || $user->hasPrivilege(Privileges::SOCIALPROOF_MANAGE);
    }

    public function view(User $user, SocialProofEvent $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::SOCIALPROOF_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, SocialProofEvent $event): bool
    {
        return $user->tenant_id === $event->tenant_id && $this->create($user);
    }

    public function delete(User $user, SocialProofEvent $event): bool
    {
        return $this->update($user, $event);
    }
}
