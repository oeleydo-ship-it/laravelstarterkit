<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Support\Privileges;

class ReviewPolicy
{
    public function viewAny(User $user): bool { return $user->canAccessModule('reviews') || $user->hasPrivilege(Privileges::REVIEWS_VIEW) || $user->hasPrivilege(Privileges::REVIEWS_MANAGE); }
    public function view(User $user, Review $review): bool { return $user->tenant_id === $review->tenant_id && $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasPrivilege(Privileges::REVIEWS_MANAGE) || $user->isOwnerOrAdmin(); }
    public function update(User $user, Review $review): bool { return $user->tenant_id === $review->tenant_id && $this->create($user); }
    public function delete(User $user, Review $review): bool { return $this->update($user, $review); }
}
