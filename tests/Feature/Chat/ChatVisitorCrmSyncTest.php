<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Client;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatVisitorCrmSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Module::firstOrCreate(
            ['key' => 'chat'],
            ['name' => 'Live Chat', 'description' => 'Chat', 'enabled_by_default' => true]
        );
        Module::firstOrCreate(
            ['key' => 'clients'],
            ['name' => 'CRM', 'description' => 'CRM', 'enabled_by_default' => true]
        );
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);

        foreach (['chat', 'clients'] as $key) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => $key,
                'enabled' => true,
            ]);
        }

        return $tenant;
    }

    public function test_saving_visitor_crm_creates_a_client_in_crm_module(): void
    {
        $tenant = $this->makeTenant();
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole('owner'),
        ]);

        $visitor = ChatVisitor::create([
            'tenant_id' => $tenant->id,
            'name' => 'contentbms',
        ]);

        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($agent)
            ->put(route('chat.conversations.visitor.update', $conversation), [
                'name' => 'contentbms',
                'email' => 'kirodanver8@gmail.com',
                'phone' => '555-0100',
                'company' => 'CodeArena',
                'city' => 'Dubai',
                'country' => 'UAE',
                'crm_notes' => 'Interested in hosting.',
            ])
            ->assertRedirect();

        $client = Client::where('email', 'kirodanver8@gmail.com')->first();
        $this->assertNotNull($client);
        $this->assertEquals('contentbms', $client->name);
        $this->assertEquals('CodeArena', $client->company);
        $this->assertEquals('Live Chat', $client->source);
        $this->assertEquals($client->id, $visitor->fresh()->client_id);

        $this->actingAs($agent)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('contentbms')
            ->assertSee('Interested in hosting.');
    }

    public function test_saving_again_updates_the_same_crm_client(): void
    {
        $tenant = $this->makeTenant();
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole('owner'),
        ]);

        $client = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Old Name',
            'email' => 'lead@example.com',
            'status' => 'lead',
        ]);

        $visitor = ChatVisitor::create([
            'tenant_id' => $tenant->id,
            'email' => 'lead@example.com',
            'client_id' => $client->id,
        ]);

        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($agent)
            ->put(route('chat.conversations.visitor.update', $conversation), [
                'name' => 'Updated Lead',
                'email' => 'lead@example.com',
                'company' => 'New Co',
            ])
            ->assertRedirect();

        $this->assertEquals(1, Client::count());
        $this->assertEquals('Updated Lead', $client->fresh()->name);
        $this->assertEquals('New Co', $client->fresh()->company);
    }
}
