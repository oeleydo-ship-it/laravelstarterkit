<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class AgentAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public User $agent)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("tenant.{$this->agent->tenant_id}.agents"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'agent.availability';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->agent->id,
            'name' => $this->agent->name,
            'availability' => $this->agent->chat_availability,
        ];
    }
}
