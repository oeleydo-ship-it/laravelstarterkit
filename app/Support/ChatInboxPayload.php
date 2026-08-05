<?php

namespace App\Support;

use App\Models\ChatConversation;
use App\Models\User;

class ChatInboxPayload
{
    public static function from(ChatConversation $conversation, ?User $viewer = null): array
    {
        $conversation->loadMissing(['assignee', 'visitor']);

        if (! isset($conversation->unread_count)) {
            $conversation->loadCount(['messages as unread_count' => function ($messages) {
                $messages->where('sender_type', 'visitor')->whereNull('read_at');
            }]);
        }

        $lastAt = $conversation->last_message_at ?? $conversation->created_at;
        $isMine = $viewer && (int) $conversation->assigned_to === (int) $viewer->id;

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'assignee_name' => $conversation->assignee?->name,
            'is_mine' => $isMine,
            'rating' => $conversation->rating,
            'is_rated' => $conversation->isRated(),
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $lastAt?->toIso8601String(),
            'last_message_at_human' => $lastAt?->diffForHumans(),
            'last_message_at_short' => $lastAt?->diffForHumans(short: true),
            'unread_count' => (int) ($conversation->unread_count ?? 0),
            'visitor_label' => $conversation->visitor?->displayName()
                ?? ('Visitor #'.$conversation->chat_visitor_id),
            'url' => route('chat.conversations.show', $conversation),
        ];
    }
}
