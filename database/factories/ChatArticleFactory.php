<?php

namespace Database\Factories;

use App\Models\ChatArticle;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatArticle>
 */
class ChatArticleFactory extends Factory
{
    protected $model = ChatArticle::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory()->withChat(),
            'title' => fake()->sentence(4),
            'keywords' => implode(', ', fake()->words(3)),
            'body' => fake()->paragraphs(2, true),
            'is_published' => true,
        ];
    }

    /**
     * A draft is hidden from the composer lookup and from AI assist.
     */
    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
