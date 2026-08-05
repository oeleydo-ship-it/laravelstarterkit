<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notes deliberately broadcast on a *separate* agent-only channel.
 * The regular conversation channel is shared with the widget visitor, so putting
 * notes there would hand staff-only commentary straight to the customer.
 */
class ChatInternalNoteAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                "tenant.{$this->message->tenant_id}.conversation.{$this->message->chat_conversation_id}.internal"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'note.added';
    }

    public function broadcastQueue(): string
    {
        return 'broadcast';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->chat_conversation_id,
            'author_name' => $this->message->sender?->name ?? 'Agent',
            'body' => $this->message->body,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
