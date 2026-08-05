<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use ReflectionClass;
use Tests\TestCase;

class ChatAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTenant(string $slug = 'acme'): Tenant
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);

        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'chat',
            'enabled' => true,
        ]);

        return $tenant;
    }

    protected function makeUser(Tenant $tenant, string $role = 'owner'): User
    {
        $privileges = \App\Support\Privileges::defaultsForRole($role);
        if ($role === 'member') {
            $privileges[] = \App\Support\Privileges::CHAT_AGENT;
        }

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
            'privileges' => array_values(array_unique($privileges)),
        ]);
    }

    /**
     * Pull the real closure registered in routes/channels.php so these assertions
     * cover the shipped authorization rule, not a copy of it.
     */
    protected function channelCallback(string $pattern): callable
    {
        $broadcaster = Broadcast::driver();

        $channels = (new ReflectionClass($broadcaster))->getParentClass()->getProperty('channels');
        $channels->setAccessible(true);

        $registered = $channels->getValue($broadcaster);

        $this->assertArrayHasKey($pattern, $registered, "Channel [$pattern] is not registered.");

        return $registered[$pattern];
    }

    public function test_agent_can_set_their_chat_availability(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');

        $this->assertEquals('offline', $agent->chat_availability);

        $this->actingAs($agent)
            ->put(route('chat.availability.update'), ['availability' => 'online'])
            ->assertRedirect();

        $agent->refresh();
        $this->assertEquals('online', $agent->chat_availability);
        $this->assertNotNull($agent->chat_last_seen_at);
        $this->assertTrue($agent->isAvailableForChat());
    }

    public function test_availability_must_be_a_known_value(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');

        $this->actingAs($agent)
            ->put(route('chat.availability.update'), ['availability' => 'invisible'])
            ->assertSessionHasErrors('availability');

        $this->assertEquals('offline', $agent->fresh()->chat_availability);
    }

    public function test_an_agent_can_only_change_their_own_availability(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');
        $colleague = $this->makeUser($tenant, 'member');

        $this->actingAs($agent)
            ->put(route('chat.availability.update'), ['availability' => 'online'])
            ->assertRedirect();

        // The endpoint only ever touches the authenticated user.
        $this->assertEquals('online', $agent->fresh()->chat_availability);
        $this->assertEquals('offline', $colleague->fresh()->chat_availability);
    }

    public function test_inactive_user_is_not_available_even_if_marked_online(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');
        $agent->update(['chat_availability' => 'online', 'status' => 'inactive']);

        $this->assertFalse($agent->fresh()->isAvailableForChat());
    }

    public function test_presence_channel_admits_an_agent_of_the_same_tenant(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');
        $agent->update(['chat_availability' => 'online']);

        $callback = $this->channelCallback('tenant.{tenantId}.agents');
        $result = $callback($agent->fresh(), $tenant->id);

        $this->assertIsArray($result);
        $this->assertEquals($agent->id, $result['id']);
        $this->assertEquals($agent->name, $result['name']);
        $this->assertEquals('online', $result['availability']);
    }

    public function test_presence_channel_payload_does_not_leak_account_details(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'owner');

        $callback = $this->channelCallback('tenant.{tenantId}.agents');
        $result = $callback($agent, $tenant->id);

        $this->assertEquals(['id', 'name', 'availability'], array_keys($result));
        $this->assertStringNotContainsString($agent->email, json_encode($result));
    }

    public function test_presence_channel_rejects_an_agent_from_another_tenant(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');
        $foreignAgent = $this->makeUser($tenantB, 'owner');

        $callback = $this->channelCallback('tenant.{tenantId}.agents');

        $this->assertFalse($callback($foreignAgent, $tenantA->id));
    }

    public function test_presence_channel_rejects_widget_visitors(): void
    {
        $tenant = $this->makeTenant();
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);

        $callback = $this->channelCallback('tenant.{tenantId}.agents');

        // Visitors must never be able to enumerate staff.
        $this->assertFalse($callback($visitor, $tenant->id));
    }

    public function test_going_online_makes_an_agent_eligible_for_routing(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'member');

        $this->assertCount(0, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->availableAgents()->get());

        $this->actingAs($agent)->put(route('chat.availability.update'), ['availability' => 'online']);

        $this->assertCount(1, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->availableAgents()->get());
    }
}
