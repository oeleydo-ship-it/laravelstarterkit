<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The widget cannot use the shared /broadcasting/auth route: visitors have no
 * session and that route is CSRF-protected. Without this endpoint the private
 * subscription fails and the visitor only sees agent replies after a refresh.
 */
class ChatWidgetChannelAuthTest extends TestCase
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

    /**
     * @return array{0: Tenant, 1: string, 2: int}
     */
    protected function startChat(string $slug = 'acme'): array
    {
        $tenant = $this->makeTenant($slug);

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        return [$tenant, $start->json('visitor_token'), $start->json('conversation_id')];
    }

    protected function channel(Tenant $tenant, int $conversationId): string
    {
        return "private-tenant.{$tenant->id}.conversation.{$conversationId}";
    }

    public function test_a_visitor_is_authorized_for_their_own_conversation_channel(): void
    {
        [$tenant, $token, $conversationId] = $this->startChat();

        $response = $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $conversationId),
            'visitor_token' => $token,
        ])->assertOk();

        $auth = $response->json('auth');
        $key = config('broadcasting.connections.'.config('broadcasting.default').'.key');
        $secret = config('broadcasting.connections.'.config('broadcasting.default').'.secret');

        // The Pusher-protocol signature Reverb will verify on subscribe.
        $this->assertEquals(
            $key.':'.hash_hmac('sha256', '123.456:'.$this->channel($tenant, $conversationId), (string) $secret),
            $auth,
        );
    }

    public function test_the_signature_is_never_returned_without_a_valid_token(): void
    {
        [$tenant, , $conversationId] = $this->startChat();

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $conversationId),
            'visitor_token' => 'not-the-right-token',
        ])->assertForbidden();
    }

    public function test_a_visitor_cannot_authorize_someone_elses_conversation(): void
    {
        [$tenant, $token] = $this->startChat();

        // A second visitor's chat in the same workspace.
        $other = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $otherConversationId = $other->json('conversation_id');

        $this->assertNotEquals($token, $other->json('visitor_token'));

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $otherConversationId),
            'visitor_token' => $token,
        ])->assertForbidden();
    }

    public function test_a_visitor_cannot_authorize_the_agents_internal_note_channel(): void
    {
        [$tenant, $token, $conversationId] = $this->startChat();

        // Staff-only channel. The pattern is anchored precisely so this cannot
        // slip through as a conversation channel with a suffix.
        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $conversationId).'.internal',
            'visitor_token' => $token,
        ])->assertForbidden();
    }

    public function test_a_visitor_cannot_authorize_the_agent_presence_channel(): void
    {
        [$tenant, $token] = $this->startChat();

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => "private-tenant.{$tenant->id}.agents",
            'visitor_token' => $token,
        ])->assertForbidden();
    }

    public function test_a_visitor_cannot_authorize_a_channel_in_another_workspace(): void
    {
        [$tenantA, $tokenA] = $this->startChat('acme');
        [$tenantB, , $conversationB] = $this->startChat('globex');

        $this->postJson(route('chat.widget.broadcasting.auth', $tenantA->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenantB, $conversationB),
            'visitor_token' => $tokenA,
        ])->assertForbidden();
    }

    public function test_a_malformed_socket_id_is_rejected(): void
    {
        [$tenant, $token, $conversationId] = $this->startChat();

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => 'not-a-socket-id',
            'channel_name' => $this->channel($tenant, $conversationId),
            'visitor_token' => $token,
        ])->assertStatus(422);
    }

    public function test_authorization_is_refused_once_the_module_is_disabled(): void
    {
        [$tenant, $token, $conversationId] = $this->startChat();

        TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('module_key', 'chat')
            ->update(['enabled' => false]);

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $conversationId),
            'visitor_token' => $token,
        ])->assertForbidden();
    }

    public function test_the_conversation_channel_matches_what_the_broadcast_event_targets(): void
    {
        [$tenant, $token, $conversationId] = $this->startChat();

        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($conversationId);

        // Guards the string the widget subscribes to against the one
        // ChatMessageSent broadcasts on — a drift here is invisible until a
        // human notices replies only arrive after a refresh.
        $this->assertEquals(
            "private-tenant.{$conversation->tenant_id}.conversation.{$conversation->id}",
            $this->channel($tenant, $conversationId),
        );

        $this->postJson(route('chat.widget.broadcasting.auth', $tenant->slug), [
            'socket_id' => '123.456',
            'channel_name' => $this->channel($tenant, $conversationId),
            'visitor_token' => $token,
        ])->assertOk();
    }
}
