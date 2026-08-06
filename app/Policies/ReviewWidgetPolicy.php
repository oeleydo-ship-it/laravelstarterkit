<?php

namespace App\Policies;

use App\Models\ReviewWidget;
use App\Models\User;
use App\Support\Privileges;

class ReviewWidgetPolicy
{
    public function viewAny(User $user): bool { return $user->canAccessModule('reviews') || $user->hasPrivilege(Privileges::REVIEWS_VIEW) || $user->hasPrivilege(Privileges::REVIEWS_MANAGE); }
    public function view(User $user, ReviewWidget $widget): bool { return $user->tenant_id === $widget->tenant_id && $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasPrivilege(Privileges::REVIEWS_MANAGE) || $user->isOwnerOrAdmin(); }
    public function update(User $user, ReviewWidget $widget): bool { return $user->tenant_id === $widget->tenant_id && $this->create($user); }
    public function delete(User $user, ReviewWidget $widget): bool { return $this->update($user, $widget); }
}
