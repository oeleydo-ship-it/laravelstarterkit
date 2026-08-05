<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientNote;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmClientsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Module::firstOrCreate(
            ['key' => 'clients'],
            ['name' => 'CRM', 'description' => 'CRM', 'enabled_by_default' => true]
        );
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);

        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'clients',
            'enabled' => true,
        ]);

        return $tenant;
    }

    protected function makeUser(Tenant $tenant, string $role = 'owner'): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole($role),
        ]);
    }

    public function test_owner_can_create_a_crm_client(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant);

        $this->actingAs($owner)
            ->post(route('clients.store'), [
                'name' => 'Jane Doe',
                'company' => 'Acme Corp',
                'email' => 'jane@acme.test',
                'phone' => '+1 555 0100',
                'status' => 'active',
                'tags' => 'vip, monthly',
                'source' => 'Referral',
                'city' => 'Dubai',
                'country' => 'UAE',
                'notes' => 'Prefers email',
            ])
            ->assertRedirect(route('clients.index'));

        $client = Client::first();
        $this->assertNotNull($client);
        $this->assertEquals('Acme Corp', $client->company);
        $this->assertEquals(['vip', 'monthly'], $client->tags);
        $this->assertEquals('active', $client->status);
    }

    public function test_clients_can_be_searched_and_filtered(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant);

        Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Alpha Lead',
            'company' => 'Alpha Inc',
            'status' => 'lead',
            'email' => 'alpha@example.com',
        ]);
        Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Beta Active',
            'company' => 'Beta LLC',
            'status' => 'active',
            'email' => 'beta@example.com',
        ]);

        $this->actingAs($owner)
            ->get(route('clients.index', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Lead')
            ->assertDontSee('Beta Active');

        $this->actingAs($owner)
            ->get(route('clients.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Beta Active')
            ->assertDontSee('Alpha Lead');
    }

    public function test_activity_notes_can_be_added_to_a_client(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant);
        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jane Doe',
            'status' => 'lead',
        ]);

        $this->actingAs($owner)
            ->post(route('clients.notes.store', $client), [
                'body' => 'Called about renewal pricing.',
            ])
            ->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('client_notes', [
            'client_id' => $client->id,
            'user_id' => $owner->id,
            'body' => 'Called about renewal pricing.',
        ]);

        $this->actingAs($owner)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Called about renewal pricing.');
    }
}
