<?php

namespace Tests\Feature\Chat;

use App\Models\ChatCannedResponse;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatProductivityTest extends TestCase
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

    // ─── Canned responses ───

    public function test_admin_can_create_a_canned_response(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->post(route('chat.canned-responses.store'), [
                'title' => 'Greeting',
                'shortcut' => '/hi',
                'body' => 'Hi there! How can I help?',
            ])
            ->assertRedirect(route('chat.canned-responses.index'));

        $this->assertDatabaseHas('chat_canned_responses', [
            'tenant_id' => $tenant->id,
            'shortcut' => '/hi',
        ]);
    }

    public function test_members_cannot_manage_canned_responses(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->post(route('chat.canned-responses.store'), [
                'title' => 'Nope',
                'body' => 'Nope',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('chat_canned_responses', 0);
    }

    public function test_members_can_still_read_canned_responses_to_use_them(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');
        ChatCannedResponse::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'body' => 'Our refunds take 5 days.',
        ]);

        $this->actingAs($member)
            ->get(route('chat.canned-responses.index'))
            ->assertOk()
            ->assertSee('Refund policy');
    }

    public function test_canned_responses_are_offered_in_the_conversation_composer(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        ChatCannedResponse::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'body' => 'Our refunds take 5 days.',
        ]);

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Refund policy');
    }

    public function test_canned_responses_are_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = ChatCannedResponse::create([
            'tenant_id' => $tenantB->id,
            'title' => 'Globex secret sauce',
            'body' => 'Internal pricing floor is $40.',
        ]);

        $agentA = $this->makeUser($tenantA);

        $this->actingAs($agentA)
            ->get(route('chat.canned-responses.index'))
            ->assertOk()
            ->assertDontSee('Globex secret sauce');

        // 404 rather than 403: the tenant global scope hides the row from route
        // model binding entirely, so we never even confirm it exists.
        $this->actingAs($agentA)
            ->get(route('chat.canned-responses.edit', $foreign))
            ->assertNotFound();

        $this->actingAs($agentA)
            ->delete(route('chat.canned-responses.destroy', $foreign))
            ->assertNotFound();

        $this->assertDatabaseHas('chat_canned_responses', ['id' => $foreign->id]);
    }

    public function test_shortcuts_are_unique_per_tenant_but_reusable_across_tenants(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        ChatCannedResponse::create([
            'tenant_id' => $tenantA->id,
            'title' => 'Greeting',
            'shortcut' => '/hi',
            'body' => 'Hello',
        ]);

        // Same shortcut in a different workspace is fine.
        $adminB = $this->makeUser($tenantB, 'admin');
        $this->actingAs($adminB)
            ->post(route('chat.canned-responses.store'), [
                'title' => 'Greeting',
                'shortcut' => '/hi',
                'body' => 'Hey',
            ])
            ->assertRedirect(route('chat.canned-responses.index'));

        // Duplicate within the same workspace is rejected.
        $adminA = $this->makeUser($tenantA, 'admin');
        $this->actingAs($adminA)
            ->post(route('chat.canned-responses.store'), [
                'title' => 'Another greeting',
                'shortcut' => '/hi',
                'body' => 'Hello again',
            ])
            ->assertSessionHasErrors('shortcut');
    }

    // ─── Transfer ───

    public function test_agent_can_transfer_a_conversation_and_a_note_records_it(): void
    {
        $tenant = $this->makeTenant();
        $from = $this->makeUser($tenant);
        $to = $this->makeUser($tenant, 'member');
        $conversation = $this->makeConversation($tenant, $from);

        $this->actingAs($from)
            ->put(route('chat.conversations.transfer', $conversation), [
                'to' => $to->id,
                'reason' => 'Billing question',
            ])
            ->assertRedirect();

        $this->assertEquals($to->id, $conversation->fresh()->assigned_to);

        $note = $conversation->messages()->where('is_internal', true)->latest('id')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString($from->name, $note->body);
        $this->assertStringContainsString($to->name, $note->body);
        $this->assertStringContainsString('Billing question', $note->body);
    }

    public function test_transfer_audit_note_is_not_visible_to_the_visitor(): void
    {
        $tenant = $this->makeTenant();
        $from = $this->makeUser($tenant);
        $to = $this->makeUser($tenant, 'member');

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');
        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($conversationId);

        app(ConversationService::class)->transfer($conversation, $from, $to, 'Escalating to a manager');

        $raw = $this->getJson(
            route('chat.widget.messages.index', [$tenant->slug, $conversationId])."?visitor_token={$token}"
        )->assertOk()->getContent();

        $this->assertStringNotContainsString('Transferred from', $raw);
        $this->assertStringNotContainsString('Escalating to a manager', $raw);
    }

    public function test_cannot_transfer_to_an_agent_from_another_tenant(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $agentA = $this->makeUser($tenantA);
        $foreignAgent = $this->makeUser($tenantB);
        $conversation = $this->makeConversation($tenantA, $agentA);

        $this->actingAs($agentA)
            ->put(route('chat.conversations.transfer', $conversation), ['to' => $foreignAgent->id])
            ->assertSessionHasErrors('to');

        $this->assertEquals($agentA->id, $conversation->fresh()->assigned_to);
    }

    public function test_cannot_transfer_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $conversation = $this->makeConversation($tenantA);
        $foreignAgent = $this->makeUser($tenantB);
        $foreignColleague = $this->makeUser($tenantB, 'member');

        $this->actingAs($foreignAgent)
            ->put(route('chat.conversations.transfer', $conversation), ['to' => $foreignColleague->id])
            ->assertForbidden();
    }

    // ─── Unread counters ───

    public function test_inbox_counts_unread_visitor_messages(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'one');
        app(MessageService::class)->sendAsVisitor($conversation, 'two');

        $response = $this->actingAs($agent)->get(route('chat.conversations.index'))->assertOk();

        $listed = $response->viewData('conversations')->firstWhere('id', $conversation->id);
        $this->assertEquals(2, $listed->unread_count);
    }

    public function test_unread_count_excludes_agent_messages_and_internal_notes(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'visitor message');
        app(MessageService::class)->sendAsAgent($conversation, $agent, 'agent reply');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'a note');

        $response = $this->actingAs($agent)->get(route('chat.conversations.index'))->assertOk();
        $listed = $response->viewData('conversations')->firstWhere('id', $conversation->id);

        $this->assertEquals(1, $listed->unread_count);
    }

    public function test_marking_read_clears_the_unread_count(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'unread please');

        $this->actingAs($agent)->post(route('chat.conversations.read', $conversation))->assertNoContent();

        $response = $this->actingAs($agent)->get(route('chat.conversations.index'))->assertOk();
        $listed = $response->viewData('conversations')->firstWhere('id', $conversation->id);

        $this->assertEquals(0, $listed->unread_count);
    }
}
