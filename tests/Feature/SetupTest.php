<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_redirects_visitors_to_superadmin_setup(): void
    {
        $this->get('/')->assertRedirect(route('setup.create'));
        $this->get(route('setup.create'))->assertOk()->assertSee('Create your super administrator');
    }

    public function test_setup_creates_and_logs_in_the_first_superadmin(): void
    {
        $response = $this->post(route('setup.store'), [
            'name' => 'Site Administrator',
            'email' => 'admin@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ]);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->is_superadmin);
        $this->assertNull($admin->tenant_id);
        $this->assertNull($admin->role);
        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_setup_cannot_create_another_superadmin(): void
    {
        User::factory()->create(['tenant_id' => null, 'role' => null, 'is_superadmin' => true]);

        $this->get(route('setup.create'))->assertRedirect(route('login'));
        $this->get('/')->assertOk();
    }

    public function test_setup_is_not_exposed_on_an_existing_installation(): void
    {
        User::factory()->create(['is_superadmin' => false]);

        $this->get(route('setup.create'))->assertRedirect(route('login'));
    }
}
