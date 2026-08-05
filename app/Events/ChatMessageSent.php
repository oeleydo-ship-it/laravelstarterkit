<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                "tenant.{$this->message->tenant_id}.conversation.{$this->message->chat_conversation_id}"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->chat_conversation_id,
            'sender_type' => $this->message->sender_type,
            'sender_id' => $this->message->sender_id,
            'sender_name' => match ($this->message->sender_type) {
                'bot' => 'Assistant',
                'agent' => $this->message->sender?->name ?? 'Support',
                default => 'Visitor',
            },
            'body' => $this->message->body,
            'attachment' => $this->message->attachment?->toPayload(),
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
