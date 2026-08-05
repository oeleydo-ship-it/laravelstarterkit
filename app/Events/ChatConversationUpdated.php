<?php

namespace App\Events;

use App\Models\ChatConversation;
use App\Support\ChatInboxPayload;
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
            // Thread page + widget
            new PrivateChannel(
                "tenant.{$this->conversation->tenant_id}.conversation.{$this->conversation->id}"
            ),
            // Agent inbox / sidebar list
            new PrivateChannel(
                "tenant.{$this->conversation->tenant_id}.inbox"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }

    public function broadcastWith(): array
    {
        return ChatInboxPayload::from($this->conversation);
    }
}
