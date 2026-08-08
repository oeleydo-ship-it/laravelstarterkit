<?php

namespace Tests\Feature\Chat;

use App\Events\ChatInternalNoteAdded;
use App\Events\ChatMessageSent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Tests\TestCase;

class ChatInternalNoteTest extends TestCase
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

    protected function makeConversation(Tenant $tenant): ChatConversation
    {
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);

        return ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
        ]);
    }

    protected function channelCallback(string $pattern): callable
    {
        $broadcaster = Broadcast::driver();

        $channels = (new ReflectionClass($broadcaster))->getParentClass()->getProperty('channels');
        $channels->setAccessible(true);

        $registered = $channels->getValue($broadcaster);

        $this->assertArrayHasKey($pattern, $registered, "Channel [$pattern] is not registered.");

        return $registered[$pattern];
    }

    public function test_agent_can_add_an_internal_note(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.notes.store', $conversation), ['body' => 'Customer sounds upset'])
            ->assertCreated()
            ->assertJsonFragment(['body' => 'Customer sounds upset']);

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'is_internal' => true,
            'body' => 'Customer sounds upset',
        ]);
    }

    /**
     * The headline guarantee of this feature.
     */
    public function test_internal_notes_are_never_returned_to_the_visitor(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($conversationId);
        $conversation->update(['assigned_to' => $agent->id]);

        app(MessageService::class)->sendAsAgent($conversation, $agent, 'Public reply the visitor should see');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'SECRET: escalate to legal');

        $response = $this->getJson(
            route('chat.widget.messages.index', [$tenant->slug, $conversationId])."?visitor_token={$token}"
        )->assertOk();

        $response->assertJsonFragment(['body' => 'Public reply the visitor should see']);

        $raw = $response->getContent();
        $this->assertStringNotContainsString('SECRET', $raw);
        $this->assertStringNotContainsString('escalate to legal', $raw);
        $this->assertCount(1, $response->json());
    }

    public function test_internal_notes_do_not_broadcast_on_the_channel_the_visitor_listens_to(): void
    {
        Event::fake([ChatMessageSent::class, ChatInternalNoteAdded::class]);

        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        app(MessageService::class)->addInternalNote($conversation, $agent, 'internal only');

        // ChatMessageSent goes to the shared visitor channel — it must not fire.
        Event::assertNotDispatched(ChatMessageSent::class);
        Event::assertDispatched(ChatInternalNoteAdded::class);
    }

    public function test_note_event_broadcasts_on_a_separate_internal_channel(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        $note = app(MessageService::class)->addInternalNote($conversation, $agent, 'internal only');

        $channels = (new ChatInternalNoteAdded($note))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertEquals(
            "private-tenant.{$tenant->id}.conversation.{$conversation->id}.internal",
            $channels[0]->name
        );
    }

    public function test_internal_channel_rejects_visitors_and_foreign_agents(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');
        $conversation = $this->makeConversation($tenantA);

        $callback = $this->channelCallback('tenant.{tenantId}.conversation.{conversationId}.internal');

        $ownAgent = $this->makeUser($tenantA);
        $foreignAgent = $this->makeUser($tenantB);
        $visitor = ChatVisitor::create(['tenant_id' => $tenantA->id]);

        $this->assertTrue((bool) $callback($ownAgent, $tenantA->id, $conversation->id));
        $this->assertFalse($callback($foreignAgent, $tenantA->id, $conversation->id));
        $this->assertFalse($callback($visitor, $tenantA->id, $conversation->id));
    }

    public function test_internal_notes_do_not_change_the_conversation_preview(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        app(MessageService::class)->sendAsVisitor($conversation, 'I need a refund');
        $previewBefore = $conversation->fresh()->last_message_preview;

        app(MessageService::class)->addInternalNote($conversation, $agent, 'Probably a chargeback risk');

        $this->assertEquals($previewBefore, $conversation->fresh()->last_message_preview);
        $this->assertEquals('I need a refund', $conversation->fresh()->last_message_preview);
    }

    public function test_agent_cannot_note_on_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreignAgent = $this->makeUser($tenantB);
        $conversation = $this->makeConversation($tenantA);

        $this->actingAs($foreignAgent)
            ->postJson(route('chat.conversations.notes.store', $conversation), ['body' => 'nosy'])
            ->assertForbidden();

        $this->assertDatabaseMissing('chat_messages', ['body' => 'nosy']);
    }

    public function test_agent_thread_shows_internal_notes(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        app(MessageService::class)->addInternalNote($conversation, $agent, 'Watch this one closely');

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Watch this one closely')
            ->assertSee('Internal note');
    }

    public function test_visitor_scope_filters_internal_messages(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        app(MessageService::class)->sendAsVisitor($conversation, 'hello');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'note');

        $this->assertCount(2, $conversation->messages()->get());
        $this->assertCount(1, $conversation->messages()->visibleToVisitor()->get());
        $this->assertFalse(
            ChatMessage::visibleToVisitor()->where('body', 'note')->exists()
        );
    }
}
