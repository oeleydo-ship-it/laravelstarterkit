<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Module;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Chat\Ai\AiProvider;
use App\Services\Chat\AiSettingsService;
use App\Services\TicketSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        User::factory()->create(['tenant_id' => null, 'role' => null, 'is_superadmin' => true]);
        $tenant = Tenant::factory()->create();
        foreach (['tickets' => 'Tickets', 'chat' => 'Live Chat'] as $key => $name) {
            Module::firstOrCreate(['key' => $key], ['name' => $name, 'is_active' => true]);
            TenantModule::create(['tenant_id' => $tenant->id, 'module_key' => $key, 'enabled' => true]);
        }
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);

        return [$tenant, $user];
    }

    public function test_agent_can_create_a_linked_ticket_from_live_chat(): void
    {
        [$tenant, $agent] = $this->context();
        $visitor = ChatVisitor::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Customer', 'email' => 'customer@example.com']);
        $conversation = ChatConversation::factory()->create(['tenant_id' => $tenant->id, 'chat_visitor_id' => $visitor->id, 'assigned_to' => $agent->id]);
        ChatMessage::factory()->create(['tenant_id' => $tenant->id, 'chat_conversation_id' => $conversation->id, 'sender_type' => 'visitor', 'body' => 'My order has not arrived.']);

        $this->actingAs($agent)->post(route('chat.conversations.ticket.store', $conversation), ['mode' => 'agent'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'tenant_id' => $tenant->id,
            'chat_conversation_id' => $conversation->id,
            'requester_email' => 'customer@example.com',
            'source' => 'live_chat',
        ]);
    }

    public function test_agent_can_add_ticket_reply_and_internal_note(): void
    {
        [$tenant, $agent] = $this->context();
        $ticket = Ticket::create([
            'tenant_id' => $tenant->id,
            'title' => 'Test ticket',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->actingAs($agent)->post(route('tickets.replies.store', $ticket), [
            'body' => 'Escalated to fulfilment.',
            'is_internal' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'is_internal' => true,
        ]);
    }

    public function test_agent_can_update_status_without_editing_the_ticket(): void
    {
        [$tenant, $agent] = $this->context();
        $ticket = Ticket::create([
            'tenant_id' => $tenant->id,
            'title' => 'Quick update',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->actingAs($agent)->patch(route('tickets.status.update', $ticket), ['status' => 'closed'])
            ->assertRedirect();

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);
    }

    public function test_ticket_settings_are_tenant_scoped(): void
    {
        [, $agent] = $this->context();

        $this->actingAs($agent)->put(route('tickets.settings.update'), [
            'default_priority' => 'high',
            'ai_creation_enabled' => '1',
            'ai_auto_create_on_close' => '0',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['tenant_id' => $agent->tenant_id, 'key' => 'tickets.workflow']);
    }

    public function test_ai_can_generate_a_ticket_when_workspace_enables_it(): void
    {
        [$tenant, $agent] = $this->context();
        $visitor = ChatVisitor::factory()->create(['tenant_id' => $tenant->id]);
        $conversation = ChatConversation::factory()->create(['tenant_id' => $tenant->id, 'chat_visitor_id' => $visitor->id]);
        ChatMessage::factory()->create(['tenant_id' => $tenant->id, 'chat_conversation_id' => $conversation->id, 'sender_type' => 'visitor', 'body' => 'Checkout is broken.']);
        Setting::set(TicketSettingsService::KEY, ['ai_creation_enabled' => true], $tenant->id);

        $provider = new class implements AiProvider
        {
            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(string $system, array $messages): string
            {
                return '{"title":"Checkout failure","description":"Customer cannot check out.","priority":"high","category":"Bug"}';
            }
        };
        $aiSettings = \Mockery::mock(AiSettingsService::class);
        $aiSettings->shouldReceive('makeProvider')->once()->andReturn($provider);
        $this->app->instance(AiSettingsService::class, $aiSettings);

        $this->actingAs($agent)->post(route('chat.conversations.ticket.store', $conversation), ['mode' => 'ai'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', ['title' => 'Checkout failure', 'priority' => 'high', 'category' => 'Bug', 'source' => 'ai']);
    }
}
