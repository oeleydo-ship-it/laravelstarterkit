<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatConversation>
 */
class ChatConversationFactory extends Factory
{
    protected $model = ChatConversation::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory()->withChat(),
            // Derived from the resolved tenant_id rather than its own factory, so
            // a conversation can never end up pointing at a visitor from a
            // different workspace.
            'chat_visitor_id' => fn (array $attributes) => ChatVisitor::factory()
                ->create(['tenant_id' => $attributes['tenant_id']])->id,
            'assigned_to' => null,
            'status' => 'open',
            'closed_at' => null,
            'last_message_at' => null,
            'last_message_preview' => null,
        ];
    }

    public function assignedTo(User $agent): static
    {
        return $this->state(fn () => [
            'tenant_id' => $agent->tenant_id,
            'assigned_to' => $agent->id,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * A visitor only ever rates a closed chat, so this implies closed().
     */
    public function rated(int $rating = 5, ?string $comment = null): static
    {
        return $this->closed()->state(fn () => [
            'rating' => $rating,
            'rating_comment' => $comment,
            'rated_at' => now(),
        ]);
    }
}
