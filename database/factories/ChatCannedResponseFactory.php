<?php

namespace Database\Factories;

use App\Models\ChatCannedResponse;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatCannedResponse>
 */
class ChatCannedResponseFactory extends Factory
{
    protected $model = ChatCannedResponse::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory()->withChat(),
            'title' => fake()->unique()->sentence(3),
            // Unique per tenant in the schema, so never generate a fixed default.
            'shortcut' => '/'.fake()->unique()->word(),
            'body' => fake()->paragraph(),
        ];
    }

    public function withoutShortcut(): static
    {
        return $this->state(fn () => ['shortcut' => null]);
    }
}
