<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['setup.required' => true]);
    }

    public function test_fresh_install_redirects_visitors_to_superadmin_setup(): void
    {
        $this->get('/')->assertRedirect(route('setup.create'));
        $this->get(route('setup.create'))->assertOk()->assertSee('Create your super administrator');
    }

    public function test_setup_creates_and_logs_in_the_first_superadmin(): void
    {
        $page = $this->get(route('setup.create'));
        preg_match('/name="setup_token" value="([^"]+)"/', $page->getContent(), $matches);

        $response = $this->post(route('setup.store'), [
            'setup_token' => $matches[1],
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

    public function test_setup_rejects_an_invalid_signed_token(): void
    {
        $this->post(route('setup.store'), [
            'setup_token' => 'invalid',
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'a-secure-password',
            'password_confirmation' => 'a-secure-password',
        ])->assertStatus(419);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_setup_cannot_create_another_superadmin(): void
    {
        User::factory()->create(['tenant_id' => null, 'role' => null, 'is_superadmin' => true]);

        $this->get(route('setup.create'))->assertRedirect(route('login'));
        $this->get('/')->assertOk();
    }

    public function test_existing_installation_without_a_superadmin_requires_setup(): void
    {
        User::factory()->create(['is_superadmin' => false]);

        $this->get('/')->assertRedirect(route('setup.create'));
        $this->get(route('setup.create'))->assertOk();
    }
}
