<?php

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tenant.{tenantId}.conversation.{conversationId}', function ($user, $tenantId, $conversationId) {
    if ($user instanceof User) {
        return (int) $user->tenant_id === (int) $tenantId;
    }

    if ($user instanceof ChatVisitor) {
        return (int) $user->tenant_id === (int) $tenantId
            && ChatConversation::withoutGlobalScopes()
                ->where('id', $conversationId)
                ->where('chat_visitor_id', $user->id)
                ->exists();
    }

    return false;
});

/**
 * Internal notes. Agents of the owning tenant only — a ChatVisitor must never be
 * authorized here, which is why this is a separate channel from the conversation
 * channel the widget subscribes to.
 */
Broadcast::channel('tenant.{tenantId}.conversation.{conversationId}.internal', function ($user, $tenantId, $conversationId) {
    if (! $user instanceof User || (int) $user->tenant_id !== (int) $tenantId) {
        return false;
    }

    return ChatConversation::withoutGlobalScopes()
        ->where('id', $conversationId)
        ->where('tenant_id', $tenantId)
        ->exists();
});

/**
 * Which agents are currently in the inbox. Agents only — visitors must never be
 * able to enumerate staff, so a ChatVisitor is rejected outright.
 */
Broadcast::channel('tenant.{tenantId}.agents', function ($user, $tenantId) {
    if (! $user instanceof User || (int) $user->tenant_id !== (int) $tenantId) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'availability' => $user->chat_availability,
    ];
});

/**
 * Live conversation list for agents on the inbox / sidebar. Visitors never join.
 */
Broadcast::channel('tenant.{tenantId}.inbox', function ($user, $tenantId) {
    return $user instanceof User && (int) $user->tenant_id === (int) $tenantId;
});
Broadcast::channel('tenant.{tenantId}.autoblog', fn ($user,$tenantId) => $user instanceof User && (int)$user->tenant_id === (int)$tenantId);
