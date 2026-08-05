<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Support\Privileges;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('tickets')
            || $user->hasPrivilege(Privileges::TICKETS_VIEW)
            || $user->hasPrivilege(Privileges::TICKETS_MANAGE);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id
            && ($user->hasPrivilege(Privileges::TICKETS_VIEW) || $user->hasPrivilege(Privileges::TICKETS_MANAGE) || $user->canAccessModule('tickets'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::TICKETS_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id
            && ($user->hasPrivilege(Privileges::TICKETS_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id
            && ($user->hasPrivilege(Privileges::TICKETS_MANAGE) || $user->isOwnerOrAdmin());
    }
}
