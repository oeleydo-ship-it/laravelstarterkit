<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\User;
use App\Support\Privileges;

class ChatConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canActAsChatAgent() || $user->hasPrivilege(Privileges::CHAT_MANAGE);
    }

    public function view(User $user, ChatConversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id
            && ($user->canActAsChatAgent() || $user->hasPrivilege(Privileges::CHAT_MANAGE));
    }

    public function update(User $user, ChatConversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id
            && ($user->canActAsChatAgent() || $user->hasPrivilege(Privileges::CHAT_MANAGE));
    }

    /**
     * Visitor-visible replies (and attachments) require the conversation to be
     * assigned to the acting agent first.
     */
    public function reply(User $user, ChatConversation $conversation): bool
    {
        return $user->canActAsChatAgent()
            && $user->tenant_id === $conversation->tenant_id
            && $conversation->assigned_to !== null
            && (int) $conversation->assigned_to === (int) $user->id
            && $conversation->status === 'open';
    }

    public function delete(User $user, ChatConversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id
            && ($user->isOwnerOrAdmin() || $user->hasPrivilege(Privileges::CHAT_MANAGE));
    }

    public function restore(User $user, ChatConversation $conversation): bool
    {
        return $user->tenant_id === $conversation->tenant_id
            && ($user->isOwnerOrAdmin() || $user->hasPrivilege(Privileges::CHAT_MANAGE));
    }
}
