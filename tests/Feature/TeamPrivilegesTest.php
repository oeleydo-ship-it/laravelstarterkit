<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPrivilegesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['clients', 'tickets', 'chat'] as $key) {
            Module::firstOrCreate(
                ['key' => $key],
                ['name' => ucfirst($key), 'description' => $key, 'enabled_by_default' => true]
            );
        }
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);

        foreach (['clients', 'tickets', 'chat'] as $key) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => $key,
                'enabled' => true,
            ]);
        }

        return $tenant;
    }

    public function test_owner_can_create_named_team_linked_to_chat_module(): void
    {
        $tenant = $this->makeTenant();
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole('owner'),
        ]);
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'status' => 'active',
            'privileges' => [Privileges::CLIENTS_VIEW],
        ]);

        $this->actingAs($owner)
            ->post(route('team.groups.store'), [
                'name' => 'Support Agents',
                'description' => 'Live chat desk',
                'module_keys' => ['chat'],
                'user_ids' => [$member->id],
            ])
            ->assertRedirect(route('team.index'));

        $team = Team::where('name', 'Support Agents')->first();
        $this->assertNotNull($team);
        $this->assertTrue($team->hasModule('chat'));
        $this->assertTrue($team->users->contains('id', $member->id));
        $this->assertTrue($member->fresh()->canActAsChatAgent());
        $this->assertTrue($member->fresh()->canAccessModule('chat'));
    }

    public function test_member_without_chat_privilege_or_team_cannot_act_as_agent(): void
    {
        $tenant = $this->makeTenant();
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'status' => 'active',
            'privileges' => [Privileges::CLIENTS_VIEW],
        ]);

        $this->assertFalse($member->canActAsChatAgent());
        $this->assertFalse($member->canAccessModule('chat'));
    }

    public function test_owner_can_update_member_privileges(): void
    {
        $tenant = $this->makeTenant();
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole('owner'),
        ]);
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'status' => 'active',
            'privileges' => [Privileges::CLIENTS_VIEW],
        ]);

        $this->actingAs($owner)
            ->put(route('team.privileges', $member), [
                'privileges' => [Privileges::CLIENTS_VIEW, Privileges::CHAT_AGENT],
            ])
            ->assertRedirect();

        $this->assertTrue($member->fresh()->hasPrivilege(Privileges::CHAT_AGENT));
        $this->assertTrue($member->fresh()->canActAsChatAgent());
    }
}
