<?php

namespace Tests\Feature\Chat;

use App\Events\ChatConversationUpdated;
use App\Events\ChatMessageSent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatConversationTest extends TestCase
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
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
        ]);
    }

    protected function makeConversation(Tenant $tenant, ?User $assignee = null): ChatConversation
    {
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);

        return ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $assignee?->id,
        ]);
    }

    public function test_agent_can_view_inbox(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);
        $this->makeConversation($tenant);

        $this->actingAs($user)
            ->get(route('chat.conversations.index'))
            ->assertOk()
            ->assertSee('Live Chat');
    }

    public function test_inbox_json_feed_returns_conversations(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $user);
        $conversation->update([
            'last_message_preview' => 'Hello from visitor',
            'last_message_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('chat.conversations.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $conversation->id)
            ->assertJsonPath('data.0.last_message_preview', 'Hello from visitor')
            ->assertJsonPath('data.0.visitor_label', 'Visitor #'.$conversation->chat_visitor_id);
    }

    public function test_agent_cannot_view_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $userA = $this->makeUser($tenantA);
        $conversationB = $this->makeConversation($tenantB);

        $this->actingAs($userA)
            ->get(route('chat.conversations.show', $conversationB))
            ->assertForbidden();
    }

    public function test_agent_cannot_send_message_to_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $userA = $this->makeUser($tenantA);
        $conversationB = $this->makeConversation($tenantB);

        $this->actingAs($userA)
            ->postJson(route('chat.conversations.messages.store', $conversationB), ['body' => 'hello'])
            ->assertForbidden();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_agent_can_send_a_message_and_it_broadcasts(): void
    {
        Event::fake([ChatMessageSent::class, ChatConversationUpdated::class]);

        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $user);

        $this->actingAs($user)
            ->postJson(route('chat.conversations.messages.store', $conversation), ['body' => 'Hi there'])
            ->assertCreated();

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'body' => 'Hi there',
        ]);

        $this->assertEquals('Hi there', $conversation->fresh()->last_message_preview);

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($conversation) {
            return $event->message->chat_conversation_id === $conversation->id
                && $event->broadcastOn()[0]->name === "private-tenant.{$conversation->tenant_id}.conversation.{$conversation->id}";
        });

        Event::assertDispatched(ChatConversationUpdated::class, function (ChatConversationUpdated $event) use ($conversation) {
            $channels = collect($event->broadcastOn())->map->name;

            return $event->conversation->id === $conversation->id
                && $channels->contains("private-tenant.{$conversation->tenant_id}.conversation.{$conversation->id}")
                && $channels->contains("private-tenant.{$conversation->tenant_id}.inbox");
        });
    }

    public function test_agent_can_close_and_reopen_a_conversation(): void
    {
        Event::fake([ChatConversationUpdated::class]);

        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        $this->actingAs($user)
            ->put(route('chat.conversations.update', $conversation), ['action' => 'close'])
            ->assertRedirect();

        $this->assertEquals('closed', $conversation->fresh()->status);

        $this->actingAs($user)
            ->put(route('chat.conversations.update', $conversation), ['action' => 'reopen'])
            ->assertRedirect();

        $this->assertEquals('open', $conversation->fresh()->status);

        Event::assertDispatchedTimes(ChatConversationUpdated::class, 2);
    }

    public function test_agent_can_assign_a_conversation(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant, 'owner');
        $member = $this->makeUser($tenant, 'member');
        $conversation = $this->makeConversation($tenant);

        $this->actingAs($owner)
            ->put(route('chat.conversations.update', $conversation), [
                'action' => 'assign',
                'assigned_to' => $member->id,
            ])
            ->assertRedirect();

        $this->assertEquals($member->id, $conversation->fresh()->assigned_to);
    }

    public function test_agent_can_unassign_a_conversation(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant, 'owner');
        $member = $this->makeUser($tenant, 'member');
        $conversation = $this->makeConversation($tenant);
        $conversation->update(['assigned_to' => $member->id]);

        $this->actingAs($owner)
            ->put(route('chat.conversations.update', $conversation), [
                'action' => 'unassign',
                'assigned_to' => '',
            ])
            ->assertRedirect();

        $this->assertNull($conversation->fresh()->assigned_to);
    }

    public function test_assign_without_an_agent_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant, 'owner');
        $conversation = $this->makeConversation($tenant);

        $this->actingAs($owner)
            ->put(route('chat.conversations.update', $conversation), [
                'action' => 'assign',
                'assigned_to' => '',
            ])
            ->assertSessionHasErrors('assigned_to');
    }

    public function test_member_cannot_delete_conversation_but_owner_can(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');
        $owner = $this->makeUser($tenant, 'owner');
        $conversation = $this->makeConversation($tenant);

        $this->actingAs($member)
            ->delete(route('chat.conversations.destroy', $conversation))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('chat.conversations.destroy', $conversation))
            ->assertRedirect();

        $this->assertSoftDeleted('chat_conversations', ['id' => $conversation->id]);
    }

    public function test_marking_read_only_affects_visitor_messages(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        $visitorMessage = ChatMessage::create([
            'tenant_id' => $tenant->id,
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'visitor',
            'body' => 'from visitor',
        ]);

        $agentMessage = ChatMessage::create([
            'tenant_id' => $tenant->id,
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'agent',
            'sender_id' => $user->id,
            'body' => 'from agent',
        ]);

        $this->actingAs($user)
            ->post(route('chat.conversations.read', $conversation))
            ->assertNoContent();

        $this->assertNotNull($visitorMessage->fresh()->read_at);
        $this->assertNull($agentMessage->fresh()->read_at);
    }

    public function test_chat_routes_are_blocked_when_module_disabled(): void
    {
        $tenant = Tenant::create(['name' => 'NoChat', 'slug' => 'nochat']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('chat.conversations.index'))
            ->assertRedirect(route('dashboard'));
    }
}
