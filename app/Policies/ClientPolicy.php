<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Support\Privileges;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessModule('clients')
            || $user->hasPrivilege(Privileges::CLIENTS_VIEW)
            || $user->hasPrivilege(Privileges::CLIENTS_MANAGE);
    }

    public function view(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && ($user->hasPrivilege(Privileges::CLIENTS_VIEW) || $user->hasPrivilege(Privileges::CLIENTS_MANAGE) || $user->canAccessModule('clients'));
    }

    public function create(User $user): bool
    {
        return $user->hasPrivilege(Privileges::CLIENTS_MANAGE) || $user->isOwnerOrAdmin();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && ($user->hasPrivilege(Privileges::CLIENTS_MANAGE) || $user->isOwnerOrAdmin());
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->tenant_id === $client->tenant_id
            && ($user->hasPrivilege(Privileges::CLIENTS_MANAGE) || $user->isOwnerOrAdmin());
    }
}
