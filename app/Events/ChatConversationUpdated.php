<?php

namespace App\Events;

use App\Models\ChatConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatConversation $conversation)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                "tenant.{$this->conversation->tenant_id}.conversation.{$this->conversation->id}"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->conversation->loadMissing('assignee');

        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'assigned_to' => $conversation->assigned_to,
            'assignee_name' => $conversation->assignee?->name,
            'rating' => $conversation->rating,
            // Lets the widget stop asking once a score is in, including in a
            // second tab the visitor left open.
            'is_rated' => $conversation->isRated(),
        ];
    }
}
