<?php

namespace Tests\Feature\Chat;

use App\Events\ChatMessageSent;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTenant(string $slug = 'acme', bool $chatEnabled = true): Tenant
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);

        if ($chatEnabled) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module_key' => 'chat',
                'enabled' => true,
            ]);
        }

        return $tenant;
    }

    protected function makeUser(Tenant $tenant, string $role = 'owner'): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_visitor_can_start_a_conversation(): void
    {
        $tenant = $this->makeTenant();

        $response = $this->postJson(route('chat.widget.start', $tenant->slug), [])
            ->assertOk()
            ->assertJsonStructure(['visitor_token', 'conversation_id', 'tenant_id']);

        $this->assertDatabaseCount('chat_visitors', 1);
        $this->assertDatabaseHas('chat_conversations', [
            'id' => $response->json('conversation_id'),
            'tenant_id' => $tenant->id,
            'status' => 'open',
        ]);
    }

    public function test_returning_visitor_resumes_the_same_conversation(): void
    {
        $tenant = $this->makeTenant();

        $first = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $first->json('visitor_token');

        $second = $this->postJson(route('chat.widget.start', $tenant->slug), ['visitor_token' => $token])->assertOk();

        $this->assertSame($first->json('conversation_id'), $second->json('conversation_id'));
        $this->assertDatabaseCount('chat_visitors', 1);
    }

    public function test_visitor_can_send_a_message_and_it_broadcasts(): void
    {
        Event::fake([ChatMessageSent::class]);

        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversationId]), [
            'body' => 'Hello, anyone there?',
            'visitor_token' => $token,
        ])->assertCreated();

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversationId,
            'sender_type' => 'visitor',
            'sender_id' => null,
            'body' => 'Hello, anyone there?',
        ]);

        Event::assertDispatched(ChatMessageSent::class);
    }

    public function test_widget_never_exposes_agent_account_details_to_visitors(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant, 'owner');

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($conversationId);
        $conversation->update(['assigned_to' => $agent->id]);
        app(MessageService::class)->sendAsAgent($conversation->fresh(), $agent, 'How can I help?');

        $response = $this->getJson(
            route('chat.widget.messages.index', [$tenant->slug, $conversationId])."?visitor_token={$token}"
        )->assertOk();

        // The agent's display name is fine; their account record is not.
        $response->assertJsonFragment(['sender_name' => $agent->name]);
        $response->assertJsonMissing(['sender' => null]);

        $raw = $response->getContent();
        $this->assertStringNotContainsString($agent->email, $raw);
        $this->assertStringNotContainsString('is_superadmin', $raw);
        $this->assertStringNotContainsString('tenant_id', $raw);
        $this->assertStringNotContainsString('"role"', $raw);
    }

    public function test_visitor_cannot_send_a_message_without_a_valid_token(): void
    {
        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $conversationId = $start->json('conversation_id');

        $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversationId]), [
            'body' => 'sneaky',
            'visitor_token' => '00000000-0000-4000-8000-000000000000',
        ])->assertForbidden();

        $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversationId]), [
            'body' => 'sneaky',
        ])->assertForbidden();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_visitor_cannot_read_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $start = $this->postJson(route('chat.widget.start', $tenantA->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        // Same conversation id, but requested through the other tenant's widget.
        $this->getJson(route('chat.widget.messages.index', [$tenantB->slug, $conversationId]) . '?visitor_token=' . $token)
            ->assertNotFound();
    }

    public function test_widget_is_unavailable_when_module_disabled(): void
    {
        $tenant = $this->makeTenant('nochat', chatEnabled: false);

        $this->postJson(route('chat.widget.start', $tenant->slug), [])
            ->assertForbidden();
    }

    public function test_widget_page_renders(): void
    {
        $tenant = $this->makeTenant();

        $this->get(route('chat.widget.show', $tenant->slug))
            ->assertOk()
            ->assertSee('Chat with ' . $tenant->name);
    }
}
