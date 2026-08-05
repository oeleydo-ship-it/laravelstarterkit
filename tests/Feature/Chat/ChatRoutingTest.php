<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\RoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatRoutingTest extends TestCase
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

    protected function makeAgent(Tenant $tenant, string $availability = 'online', string $role = 'member'): User
    {
        $privileges = \App\Support\Privileges::defaultsForRole($role);
        if ($role === 'member') {
            $privileges[] = \App\Support\Privileges::CHAT_AGENT;
        }

        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
            'chat_availability' => $availability,
            'privileges' => array_values(array_unique($privileges)),
        ]);
    }

    protected function setStrategy(Tenant $tenant, string $strategy): void
    {
        Setting::set(RoutingService::SETTING_KEY, $strategy, $tenant->id);
    }

    protected function startConversation(Tenant $tenant): ChatConversation
    {
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);

        return app(ConversationService::class)->startForVisitor($tenant, $visitor);
    }

    public function test_manual_strategy_leaves_conversations_unassigned(): void
    {
        $tenant = $this->makeTenant();
        $this->makeAgent($tenant, 'online');
        $this->setStrategy($tenant, RoutingService::STRATEGY_MANUAL);

        $this->assertNull($this->startConversation($tenant)->assigned_to);
    }

    public function test_manual_is_the_default_when_nothing_is_configured(): void
    {
        $tenant = $this->makeTenant();
        $this->makeAgent($tenant, 'online');

        $this->assertNull($this->startConversation($tenant)->assigned_to);
    }

    public function test_least_busy_picks_the_agent_with_fewest_open_chats(): void
    {
        $tenant = $this->makeTenant();
        $busy = $this->makeAgent($tenant, 'online');
        $idle = $this->makeAgent($tenant, 'online');
        $this->setStrategy($tenant, RoutingService::STRATEGY_LEAST_BUSY);

        ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => ChatVisitor::create(['tenant_id' => $tenant->id])->id,
            'status' => 'open',
            'assigned_to' => $busy->id,
        ]);

        $this->assertEquals($idle->id, $this->startConversation($tenant)->assigned_to);
    }

    public function test_least_busy_ignores_closed_conversations_when_counting_load(): void
    {
        $tenant = $this->makeTenant();
        $agentA = $this->makeAgent($tenant, 'online');
        $agentB = $this->makeAgent($tenant, 'online');
        $this->setStrategy($tenant, RoutingService::STRATEGY_LEAST_BUSY);

        // A has 3 *closed* chats — that is historical load, not current load.
        foreach (range(1, 3) as $i) {
            ChatConversation::create([
                'tenant_id' => $tenant->id,
                'chat_visitor_id' => ChatVisitor::create(['tenant_id' => $tenant->id])->id,
                'status' => 'closed',
                'assigned_to' => $agentA->id,
            ]);
        }

        // B has one chat still open, so A should be picked despite the history.
        ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => ChatVisitor::create(['tenant_id' => $tenant->id])->id,
            'status' => 'open',
            'assigned_to' => $agentB->id,
        ]);

        $this->assertEquals($agentA->id, $this->startConversation($tenant)->assigned_to);
    }

    public function test_routing_only_considers_online_agents(): void
    {
        $tenant = $this->makeTenant();
        $this->makeAgent($tenant, 'away');
        $this->makeAgent($tenant, 'offline');
        $online = $this->makeAgent($tenant, 'online');
        $this->setStrategy($tenant, RoutingService::STRATEGY_ROUND_ROBIN);

        $this->assertEquals($online->id, $this->startConversation($tenant)->assigned_to);
    }

    public function test_routing_falls_back_to_unassigned_when_nobody_is_online(): void
    {
        $tenant = $this->makeTenant();
        $this->makeAgent($tenant, 'away');
        $this->setStrategy($tenant, RoutingService::STRATEGY_LEAST_BUSY);

        $this->assertNull($this->startConversation($tenant)->assigned_to);
    }

    public function test_routing_never_assigns_an_agent_from_another_tenant(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreignAgent = $this->makeAgent($tenantB, 'online');
        $this->setStrategy($tenantA, RoutingService::STRATEGY_LEAST_BUSY);

        // Tenant A has nobody online; tenant B's agent must not be borrowed.
        $conversation = $this->startConversation($tenantA);

        $this->assertNull($conversation->assigned_to);
        $this->assertNotEquals($foreignAgent->id, $conversation->assigned_to);
    }

    public function test_round_robin_rotates_across_available_agents(): void
    {
        $tenant = $this->makeTenant();
        $first = $this->makeAgent($tenant, 'online');
        $second = $this->makeAgent($tenant, 'online');
        $this->setStrategy($tenant, RoutingService::STRATEGY_ROUND_ROBIN);

        $assigned = [
            $this->startConversation($tenant)->assigned_to,
            $this->startConversation($tenant)->assigned_to,
        ];

        // Both agents should have been used once, in some order.
        sort($assigned);
        $expected = [$first->id, $second->id];
        sort($expected);

        $this->assertEquals($expected, $assigned);
    }

    public function test_inactive_agents_are_never_routed_to(): void
    {
        $tenant = $this->makeTenant();
        $inactive = $this->makeAgent($tenant, 'online');
        $inactive->update(['status' => 'inactive']);
        $this->setStrategy($tenant, RoutingService::STRATEGY_LEAST_BUSY);

        $this->assertNull($this->startConversation($tenant)->assigned_to);
    }

    public function test_an_invalid_stored_strategy_falls_back_to_manual(): void
    {
        $tenant = $this->makeTenant();
        $this->makeAgent($tenant, 'online');
        Setting::set(RoutingService::SETTING_KEY, 'nonsense_strategy', $tenant->id);

        $this->assertNull($this->startConversation($tenant)->assigned_to);
    }

    public function test_admin_can_change_the_routing_strategy(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAgent($tenant, 'online', 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.update'), ['routing_strategy' => RoutingService::STRATEGY_LEAST_BUSY])
            ->assertRedirect();

        $this->assertEquals(
            RoutingService::STRATEGY_LEAST_BUSY,
            app(RoutingService::class)->strategyFor($tenant->fresh())
        );
    }

    public function test_routing_settings_reject_an_unknown_strategy(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAgent($tenant, 'online', 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.update'), ['routing_strategy' => 'telepathy'])
            ->assertSessionHasErrors('routing_strategy');
    }

    public function test_members_cannot_change_routing_settings(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeAgent($tenant, 'online', 'member');

        $this->actingAs($member)
            ->get(route('chat.settings.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($member)
            ->put(route('chat.settings.update'), ['routing_strategy' => RoutingService::STRATEGY_LEAST_BUSY])
            ->assertRedirect(route('dashboard'));

        $this->assertEquals(
            RoutingService::STRATEGY_MANUAL,
            app(RoutingService::class)->strategyFor($tenant->fresh())
        );
    }

    public function test_routing_settings_page_renders_for_admin(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAgent($tenant, 'online', 'admin');

        $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->assertSee('Conversation Routing')
            ->assertSee('Round robin', false);
    }
}
