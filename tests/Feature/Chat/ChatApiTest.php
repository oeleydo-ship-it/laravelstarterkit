<?php

namespace Tests\Feature\Chat;

use App\Models\ChatApiToken;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\ApiTokenService;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTenant(string $slug = 'acme', bool $chatEnabled = true): Tenant
    {
        $tenant = Tenant::create(['name' => ucfirst($slug), 'slug' => $slug]);

        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'chat',
            'enabled' => $chatEnabled,
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
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id, 'name' => 'Ada']);

        return ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $assignee?->id,
        ]);
    }

    protected function token(Tenant $tenant): string
    {
        [, $plain] = app(ApiTokenService::class)->issue($tenant, 'Test token');

        return $plain;
    }

    protected function asApi(string $token): array
    {
        return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
    }

    // ─── Authentication ───

    public function test_the_api_rejects_a_request_without_a_token(): void
    {
        $this->getJson(route('api.chat.conversations.index'))->assertStatus(401);
    }

    public function test_the_api_rejects_an_unknown_token(): void
    {
        $this->getJson(route('api.chat.conversations.index'), $this->asApi('chat_not-a-real-token'))
            ->assertStatus(401);
    }

    public function test_a_revoked_token_stops_working(): void
    {
        $tenant = $this->makeTenant();
        $token = $this->token($tenant);

        $this->getJson(route('api.chat.conversations.index'), $this->asApi($token))->assertOk();

        ChatApiToken::withoutGlobalScopes()->firstOrFail()->delete();

        $this->getJson(route('api.chat.conversations.index'), $this->asApi($token))->assertStatus(401);
    }

    public function test_tokens_are_stored_hashed_not_in_plaintext(): void
    {
        $tenant = $this->makeTenant();
        $plain = $this->token($tenant);

        $stored = ChatApiToken::withoutGlobalScopes()->firstOrFail();

        $this->assertNotEquals($plain, $stored->token_hash);
        $this->assertEquals(ApiTokenService::hash($plain), $stored->token_hash);
        $this->assertDatabaseMissing('chat_api_tokens', ['token_hash' => $plain]);
    }

    public function test_the_api_is_closed_when_the_chat_module_is_disabled(): void
    {
        $tenant = $this->makeTenant('acme', chatEnabled: false);

        $this->getJson(route('api.chat.conversations.index'), $this->asApi($this->token($tenant)))
            ->assertStatus(403);
    }

    public function test_last_used_is_recorded(): void
    {
        $tenant = $this->makeTenant();
        $token = $this->token($tenant);

        $this->assertNull(ChatApiToken::withoutGlobalScopes()->firstOrFail()->last_used_at);

        $this->getJson(route('api.chat.conversations.index'), $this->asApi($token))->assertOk();

        $this->assertNotNull(ChatApiToken::withoutGlobalScopes()->firstOrFail()->last_used_at);
    }

    // ─── Reading ───

    public function test_it_lists_conversations_for_the_tokens_workspace(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);
        app(MessageService::class)->sendAsVisitor($conversation, 'hello api');

        $this->getJson(route('api.chat.conversations.index'), $this->asApi($this->token($tenant)))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.visitor.name', 'Ada')
            ->assertJsonPath('data.0.assigned_to', $agent->name);
    }

    public function test_it_never_lists_another_workspaces_conversations(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = $this->makeConversation($tenantB);
        app(MessageService::class)->sendAsVisitor($foreign, 'globex confidential');

        $response = $this->getJson(route('api.chat.conversations.index'), $this->asApi($this->token($tenantA)))
            ->assertOk()
            ->assertJsonPath('meta.total', 0);

        $this->assertStringNotContainsString('globex confidential', $response->getContent());
    }

    public function test_showing_another_workspaces_conversation_is_a_404(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = $this->makeConversation($tenantB);

        $this->getJson(route('api.chat.conversations.show', $foreign), $this->asApi($this->token($tenantA)))
            ->assertNotFound();
    }

    public function test_show_returns_the_transcript_without_internal_notes(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'a visitor question');
        app(MessageService::class)->sendAsAgent($conversation, $agent, 'an agent answer');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'staff only shorthand');

        $response = $this->getJson(
            route('api.chat.conversations.show', $conversation),
            $this->asApi($this->token($tenant))
        )->assertOk()->assertJsonCount(2, 'data.messages');

        $this->assertStringNotContainsString('staff only shorthand', $response->getContent());
    }

    public function test_conversations_can_be_filtered_by_status(): void
    {
        $tenant = $this->makeTenant();
        $open = $this->makeConversation($tenant);
        $closed = $this->makeConversation($tenant);
        $closed->update(['status' => 'closed', 'closed_at' => Carbon::now()]);

        $this->getJson(route('api.chat.conversations.index', ['status' => 'closed']), $this->asApi($this->token($tenant)))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $closed->id);

        $this->getJson(route('api.chat.conversations.index', ['status' => 'open']), $this->asApi($this->token($tenant)))
            ->assertOk()
            ->assertJsonPath('data.0.id', $open->id);
    }

    // ─── Writing ───

    public function test_it_can_post_a_reply_as_a_named_agent(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->postJson(
            route('api.chat.conversations.messages.store', $conversation),
            ['body' => 'Replying over the API', 'agent_id' => $agent->id],
            $this->asApi($this->token($tenant))
        )
            ->assertCreated()
            ->assertJsonPath('data.body', 'Replying over the API')
            ->assertJsonPath('data.sender_name', $agent->name);

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_id' => $agent->id,
            'is_internal' => false,
        ]);
    }

    public function test_it_refuses_to_post_as_an_agent_from_another_workspace(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $conversation = $this->makeConversation($tenantA, $this->makeUser($tenantA));
        $foreignAgent = $this->makeUser($tenantB);

        $this->postJson(
            route('api.chat.conversations.messages.store', $conversation),
            ['body' => 'impersonation attempt', 'agent_id' => $foreignAgent->id],
            $this->asApi($this->token($tenantA))
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('agent_id');
    }

    public function test_posting_to_another_workspaces_conversation_is_a_404(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = $this->makeConversation($tenantB, $this->makeUser($tenantB));

        $this->postJson(
            route('api.chat.conversations.messages.store', $foreign),
            ['body' => 'should not land'],
            $this->asApi($this->token($tenantA))
        )->assertNotFound();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_a_message_needs_a_body(): void
    {
        $tenant = $this->makeTenant();
        $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant);

        $this->postJson(
            route('api.chat.conversations.messages.store', $conversation),
            [],
            $this->asApi($this->token($tenant))
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    public function test_it_can_close_a_conversation(): void
    {
        $tenant = $this->makeTenant();
        $conversation = $this->makeConversation($tenant);

        $this->postJson(
            route('api.chat.conversations.close', $conversation),
            [],
            $this->asApi($this->token($tenant))
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'closed');

        $this->assertNotNull($conversation->fresh()->closed_at);
    }

    // ─── Token management UI ───

    public function test_an_admin_can_issue_and_revoke_a_token(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->post(route('chat.settings.tokens.store'), ['name' => 'CRM sync'])
            ->assertRedirect()
            // Shown exactly once, in the flash.
            ->assertSessionHas('new_api_token');

        $token = ChatApiToken::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('chat.settings.tokens.destroy', $token))
            ->assertRedirect();

        $this->assertDatabaseCount('chat_api_tokens', 0);
    }

    public function test_members_cannot_issue_tokens(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->post(route('chat.settings.tokens.store'), ['name' => 'Sneaky'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('chat_api_tokens', 0);
    }

    public function test_an_admin_cannot_revoke_another_workspaces_token(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $this->token($tenantB);
        $foreign = ChatApiToken::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->makeUser($tenantA, 'admin'))
            ->delete(route('chat.settings.tokens.destroy', $foreign))
            ->assertNotFound();

        $this->assertDatabaseCount('chat_api_tokens', 1);
    }
}
