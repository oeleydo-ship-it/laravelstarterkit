<?php

namespace App\Policies;

use App\Models\BookingService;
use App\Models\User;
use App\Support\Privileges;

class BookingServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('bookings')
            || $user->hasPrivilege(Privileges::BOOKINGS_VIEW)
            || $user->hasPrivilege(Privileges::BOOKINGS_MANAGE);
    }

    public function view(User $user, BookingService $service): bool
    {
        return $user->tenant_id === $service->tenant_id
            && ($user->hasPrivilege(Privileges::BOOKINGS_VIEW)
                || $user->hasPrivilege(Privileges::BOOKINGS_MANAGE)
                || $user->canAccessModule('bookings'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::BOOKINGS_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, BookingService $service): bool
    {
        return $user->tenant_id === $service->tenant_id
            && ($user->hasPrivilege(Privileges::BOOKINGS_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, BookingService $service): bool
    {
        return $this->update($user, $service);
    }
}
