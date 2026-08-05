<?php

namespace Database\Factories;

use App\Models\ChatVisitor;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChatVisitor>
 */
class ChatVisitorFactory extends Factory
{
    protected $model = ChatVisitor::class;

    public function definition(): array
    {
        return [
            // Every chat factory falls back to its own workspace so a model can
            // be built standalone; pass tenant_id to place it in an existing one.
            'tenant_id' => Tenant::factory()->withChat(),
            'token' => (string) Str::uuid(),
            'name' => null,
            'email' => null,
            'last_seen_at' => now(),
        ];
    }

    /**
     * A visitor who filled in the pre-chat form.
     */
    public function identified(): static
    {
        return $this->state(fn () => [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }
}
