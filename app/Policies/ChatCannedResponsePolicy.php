<?php

namespace App\Policies;

use App\Models\ChatCannedResponse;
use App\Models\User;

class ChatCannedResponsePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ChatCannedResponse $response): bool
    {
        return $user->tenant_id === $response->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isOwnerOrAdmin();
    }

    public function update(User $user, ChatCannedResponse $response): bool
    {
        return $user->tenant_id === $response->tenant_id && $user->isOwnerOrAdmin();
    }

    public function delete(User $user, ChatCannedResponse $response): bool
    {
        return $user->tenant_id === $response->tenant_id && $user->isOwnerOrAdmin();
    }
}
