<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\WidgetSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatWidgetEmbedTest extends TestCase
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

    protected function enablePreChat(Tenant $tenant, array $overrides = []): void
    {
        Setting::set(
            WidgetSettingsService::SETTING_KEY,
            array_merge(WidgetSettingsService::defaults(), array_merge([
                'pre_chat_enabled' => true,
                'pre_chat_message' => 'Tell us who you are.',
            ], $overrides)),
            $tenant->id,
        );
    }

    // ─── Embed loader ───

    public function test_the_embed_script_is_served_as_javascript(): void
    {
        $tenant = $this->makeTenant();

        $response = $this->get(route('chat.widget.embed', $tenant->slug))->assertOk();

        $this->assertStringContainsString('application/javascript', $response->headers->get('Content-Type'));
    }

    public function test_the_embed_script_points_at_this_tenants_widget(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $script = $this->get(route('chat.widget.embed', $tenantA->slug))->assertOk()->getContent();

        $this->assertStringContainsString(route('chat.widget.show', $tenantA->slug), $script);
        $this->assertStringNotContainsString(route('chat.widget.show', $tenantB->slug), $script);
    }

    public function test_the_embed_script_guards_against_being_included_twice(): void
    {
        $tenant = $this->makeTenant();

        $script = $this->get(route('chat.widget.embed', $tenant->slug))->assertOk()->getContent();

        $this->assertStringContainsString('__chatWidgetLoaded', $script);
    }

    public function test_the_embed_script_only_accepts_resize_messages_from_this_app(): void
    {
        $tenant = $this->makeTenant();

        $script = $this->get(route('chat.widget.embed', $tenant->slug))->assertOk()->getContent();

        // The host page must not act on postMessage from arbitrary origins.
        $this->assertStringContainsString('event.origin !== ORIGIN', $script);
    }

    public function test_the_declared_origin_matches_the_iframe_it_will_host(): void
    {
        $tenant = $this->makeTenant();

        $script = $this->get(route('chat.widget.embed', $tenant->slug))->assertOk()->getContent();

        // A mismatch here (a port, or a stale APP_URL) makes the host page
        // reject every resize message, with no error raised anywhere.
        $widgetUrl = route('chat.widget.show', $tenant->slug);
        $parts = parse_url($widgetUrl);
        $expected = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

        $this->assertStringContainsString('var ORIGIN = "'.$expected.'"', $script);
        $this->assertStringStartsWith($expected, $widgetUrl);
    }

    public function test_the_embed_script_is_unavailable_when_the_module_is_off(): void
    {
        $tenant = $this->makeTenant();

        TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->update(['enabled' => false]);

        $this->get(route('chat.widget.embed', $tenant->slug))->assertForbidden();
    }

    public function test_the_embed_script_404s_for_an_unknown_workspace(): void
    {
        $this->get(route('chat.widget.embed', 'no-such-workspace'))->assertNotFound();
    }

    // ─── Pre-chat form ───

    public function test_the_pre_chat_form_is_off_by_default(): void
    {
        $tenant = $this->makeTenant();

        $this->get(route('chat.widget.show', $tenant->slug))
            ->assertOk()
            ->assertSee('data-pre-chat="0"', false);
    }

    public function test_enabling_pre_chat_renders_the_form_and_its_prompt(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        $this->get(route('chat.widget.show', $tenant->slug))
            ->assertOk()
            ->assertSee('data-pre-chat="1"', false)
            ->assertSee('chat-widget-prechat', false)
            ->assertSee('Tell us who you are.');
    }

    public function test_a_visitors_details_are_recorded_when_the_chat_starts(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        $this->postJson(route('chat.widget.start', $tenant->slug), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->assertOk();

        $this->assertDatabaseHas('chat_visitors', [
            'tenant_id' => $tenant->id,
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
    }

    public function test_a_malformed_email_is_rejected_before_a_chat_is_created(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        $this->postJson(route('chat.widget.start', $tenant->slug), [
            'name' => 'Ada',
            'email' => 'not-an-email',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('chat_conversations', 0);
        $this->assertDatabaseCount('chat_visitors', 0);
    }

    public function test_the_details_survive_into_the_agent_inbox(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        $this->postJson(route('chat.widget.start', $tenant->slug), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->assertOk();

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->actingAs($agent)
            ->get(route('chat.conversations.index'))
            ->assertOk()
            ->assertSee('Ada Lovelace');
    }

    public function test_a_returning_visitor_keeps_their_details_when_they_are_not_resubmitted(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        $first = $this->postJson(route('chat.widget.start', $tenant->slug), [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ])->assertOk();

        // Coming back with only the token must not blank out who they are.
        $this->postJson(route('chat.widget.start', $tenant->slug), [
            'visitor_token' => $first->json('visitor_token'),
        ])->assertOk();

        $this->assertDatabaseHas('chat_visitors', [
            'token' => $first->json('visitor_token'),
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
        ]);
    }

    public function test_details_cannot_be_written_onto_another_workspaces_visitor(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreignVisitor = ChatVisitor::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Globex Customer',
        ]);

        // Replaying another workspace's token starts a fresh visitor here rather
        // than reaching across the tenant boundary.
        $this->postJson(route('chat.widget.start', $tenantA->slug), [
            'visitor_token' => $foreignVisitor->token,
            'name' => 'Impersonator',
        ])->assertOk();

        $this->assertEquals('Globex Customer', $foreignVisitor->fresh()->name);
        $this->assertDatabaseHas('chat_visitors', [
            'tenant_id' => $tenantA->id,
            'name' => 'Impersonator',
        ]);
    }

    public function test_admins_can_turn_pre_chat_on_from_the_appearance_tab(): void
    {
        $tenant = $this->makeTenant();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->put(route('chat.settings.appearance'), [
                'title' => 'Acme Support',
                'greeting' => 'Hi',
                'launcher_text' => 'Chat',
                'color' => '#0d6efd',
                'offline_message' => 'Away',
                'pre_chat_enabled' => '1',
                'pre_chat_message' => 'Your details, please.',
            ])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'appearance']));

        $appearance = app(WidgetSettingsService::class)->for($tenant->fresh());

        $this->assertTrue($appearance['pre_chat_enabled']);
        $this->assertEquals('Your details, please.', $appearance['pre_chat_message']);
    }

    public function test_no_conversation_exists_until_the_visitor_submits_the_form(): void
    {
        $tenant = $this->makeTenant();
        $this->enablePreChat($tenant);

        // Rendering the widget must not create anything on its own.
        $this->get(route('chat.widget.show', $tenant->slug))->assertOk();

        $this->assertDatabaseCount('chat_conversations', 0);

        $this->postJson(route('chat.widget.start', $tenant->slug), ['name' => 'Ada'])->assertOk();

        $this->assertEquals(1, ChatConversation::withoutGlobalScopes()->count());
    }
}
