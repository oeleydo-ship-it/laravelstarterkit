<?php

namespace App\Policies;

use App\Models\BookingAppointment;
use App\Models\User;
use App\Support\Privileges;

class BookingAppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('bookings')
            || $user->hasPrivilege(Privileges::BOOKINGS_VIEW)
            || $user->hasPrivilege(Privileges::BOOKINGS_MANAGE);
    }

    public function update(User $user, BookingAppointment $appointment): bool
    {
        return $user->tenant_id === $appointment->tenant_id
            && ($user->hasPrivilege(Privileges::BOOKINGS_MANAGE) || $user->isOwnerOrAdmin());
    }
}
