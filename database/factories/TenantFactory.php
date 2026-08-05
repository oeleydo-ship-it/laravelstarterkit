<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
        ];
    }

    /**
     * A workspace with the chat module switched on — the state almost every
     * chat test and factory needs, since the routes 403 without it.
     */
    public function withChat(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            TenantModule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'module_key' => 'chat'],
                ['enabled' => true],
            );
        });
    }
}
