<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::factory()->create(['tenant_id' => null, 'role' => null, 'is_superadmin' => true]);
    }

    public function test_superadmin_dashboard_redirects_away_from_tenant_dashboard(): void
    {
        $this->actingAs($this->superadmin())->get('/dashboard')
            ->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_superadmin_can_open_management_pages(): void
    {
        $admin = $this->superadmin();
        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('superadmin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('superadmin.tenants.index'))->assertOk();
        $this->actingAs($admin)->get(route('superadmin.settings'))->assertOk();
        $this->actingAs($admin)->get(route('superadmin.plans.index'))->assertOk();
    }

    public function test_superadmin_can_manage_a_workspace_plan_and_modules(): void
    {
        $admin = $this->superadmin();
        $plan = Plan::create([
            'key' => 'pro', 'name' => 'Pro', 'price_monthly' => 29,
            'price_yearly' => 290, 'limits' => ['max_users' => 10],
            'is_active' => true, 'sort_order' => 1,
        ]);
        $tenant = Tenant::factory()->create();
        Module::firstOrCreate(['key' => 'clients'], ['name' => 'CRM']);

        $this->actingAs($admin)->put(route('superadmin.tenants.update', $tenant), [
            'name' => 'Updated Workspace', 'slug' => 'updated-workspace', 'plan_id' => $plan->id,
            'modules' => ['clients'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Updated Workspace', 'plan_id' => $plan->id]);
        $this->assertTrue(TenantModule::where('tenant_id', $tenant->id)->where('module_key', 'clients')->where('enabled', true)->exists());
    }

    public function test_non_superadmin_cannot_access_system_management(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'is_superadmin' => false]);
        $this->actingAs($user)->get(route('superadmin.dashboard'))->assertForbidden();
    }
}
