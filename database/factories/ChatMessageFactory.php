<?php

namespace Database\Factories;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    protected $model = ChatMessage::class;

    public function definition(): array
    {
        $conversation = ChatConversation::factory();

        return [
            'chat_conversation_id' => $conversation,
            'tenant_id' => fn (array $attributes) => ChatConversation::withoutGlobalScopes()
                ->findOrFail($attributes['chat_conversation_id'])->tenant_id,
            'sender_type' => 'visitor',
            'sender_id' => null,
            'body' => fake()->sentence(),
            'is_internal' => false,
            'read_at' => null,
        ];
    }

    public function fromAgent(User $agent): static
    {
        return $this->state(fn () => [
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
        ]);
    }

    /**
     * A staff-only note. Never visible to the visitor, and never counted as a
     * first response in the reports.
     */
    public function internalNote(User $agent): static
    {
        return $this->fromAgent($agent)->state(fn () => ['is_internal' => true]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
