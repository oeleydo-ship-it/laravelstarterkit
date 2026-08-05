<?php

namespace Tests\Feature\Chat;

use App\Jobs\SendChatAlert;
use App\Jobs\SendChatWebhook;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Notifications\ChatConversationAssigned;
use App\Notifications\ChatConversationStarted;
use App\Services\Chat\ConversationService;
use App\Services\Chat\IntegrationSettingsService;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatIntegrationTest extends TestCase
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

    protected function makeUser(Tenant $tenant, string $role = 'owner', string $availability = 'offline'): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => $role,
            'status' => 'active',
            'chat_availability' => $availability,
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

    protected function configure(Tenant $tenant, array $values): array
    {
        return app(IntegrationSettingsService::class)->save($tenant, $values);
    }

    // ─── Settings ───

    public function test_a_signing_secret_is_generated_when_a_webhook_url_is_set(): void
    {
        $tenant = $this->makeTenant();

        $settings = $this->configure($tenant, ['webhook_url' => 'https://example.test/hooks/chat']);

        $this->assertNotEmpty($settings['webhook_secret']);
    }

    public function test_plaintext_webhook_urls_are_rejected(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.integrations'), ['webhook_url' => 'http://example.test/hook'])
            ->assertSessionHasErrors('webhook_url');
    }

    public function test_members_cannot_change_integrations(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->put(route('chat.settings.integrations'), ['mail_enabled' => '1'])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => IntegrationSettingsService::SETTING_KEY]);
    }

    // ─── Alerts & webhooks are queued, not sent inline ───

    public function test_starting_a_conversation_queues_an_alert_and_a_webhook(): void
    {
        Bus::fake();

        $tenant = $this->makeTenant();

        $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        Bus::assertDispatched(SendChatAlert::class,
            fn (SendChatAlert $job) => $job->tenantId === $tenant->id);

        Bus::assertDispatched(SendChatWebhook::class,
            fn (SendChatWebhook $job) => $job->event === 'conversation.created');
    }

    public function test_visitor_messages_are_webhooked_but_agent_replies_are_not(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        Bus::fake();

        app(MessageService::class)->sendAsVisitor($conversation, 'hello?');
        Bus::assertDispatched(SendChatWebhook::class,
            fn (SendChatWebhook $job) => $job->event === 'message.created');

        Bus::fake();

        // Echoing our own replies back out is how integrations end up looping.
        app(MessageService::class)->sendAsAgent($conversation, $agent, 'hi there');
        Bus::assertNotDispatched(SendChatWebhook::class);
    }

    public function test_closing_a_conversation_queues_a_webhook(): void
    {
        $tenant = $this->makeTenant();
        $conversation = $this->makeConversation($tenant);

        Bus::fake();

        app(ConversationService::class)->close($conversation);

        Bus::assertDispatched(SendChatWebhook::class,
            fn (SendChatWebhook $job) => $job->event === 'conversation.closed');
    }

    // ─── Delivery ───

    public function test_the_alert_job_posts_to_every_configured_destination(): void
    {
        $tenant = $this->makeTenant();

        $this->configure($tenant, [
            'slack_webhook_url' => 'https://hooks.slack.test/abc',
            'discord_webhook_url' => 'https://discord.test/api/webhooks/abc',
            'telegram_bot_token' => 'bot-token',
            'telegram_chat_id' => '12345',
        ]);

        Http::fake();

        (new SendChatAlert($tenant->id, 'New chat from Ada', 'https://app.test/chat/1'))
            ->handle(app(IntegrationSettingsService::class));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'hooks.slack.test')
            && str_contains($r['text'], 'New chat from Ada'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'discord.test')
            && str_contains($r['content'], 'New chat from Ada'));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org/botbot-token/sendMessage')
            && $r['chat_id'] === '12345');
    }

    public function test_one_failing_destination_does_not_stop_the_others(): void
    {
        $tenant = $this->makeTenant();

        $this->configure($tenant, [
            'slack_webhook_url' => 'https://hooks.slack.test/abc',
            'discord_webhook_url' => 'https://discord.test/api/webhooks/abc',
        ]);

        Http::fake([
            'hooks.slack.test/*' => Http::response('nope', 500),
            'discord.test/*' => Http::response('ok'),
        ]);

        (new SendChatAlert($tenant->id, 'New chat', null))
            ->handle(app(IntegrationSettingsService::class));

        Http::assertSent(fn ($r) => str_contains($r->url(), 'discord.test'));
    }

    public function test_nothing_is_posted_when_no_destination_is_configured(): void
    {
        $tenant = $this->makeTenant();

        Http::fake();

        (new SendChatAlert($tenant->id, 'New chat', null))
            ->handle(app(IntegrationSettingsService::class));

        Http::assertNothingSent();
    }

    public function test_the_webhook_job_signs_the_exact_body_it_sends(): void
    {
        $tenant = $this->makeTenant();

        $settings = $this->configure($tenant, [
            'webhook_url' => 'https://example.test/hooks/chat',
            'webhook_secret' => 'a-secret-at-least-16-chars',
        ]);

        Http::fake();

        (new SendChatWebhook($tenant->id, 'conversation.created', ['id' => 7]))
            ->handle(app(IntegrationSettingsService::class));

        Http::assertSent(function ($request) use ($settings) {
            $body = $request->body();
            $expected = 'sha256='.hash_hmac('sha256', $body, $settings['webhook_secret']);

            return $request->hasHeader(SendChatWebhook::SIGNATURE_HEADER, $expected)
                && json_decode($body, true)['event'] === 'conversation.created'
                && json_decode($body, true)['data']['id'] === 7;
        });
    }

    public function test_the_webhook_job_does_nothing_without_an_endpoint(): void
    {
        $tenant = $this->makeTenant();

        Http::fake();

        (new SendChatWebhook($tenant->id, 'conversation.created', []))
            ->handle(app(IntegrationSettingsService::class));

        Http::assertNothingSent();
    }

    // ─── Email ───

    public function test_no_email_is_sent_while_mail_notifications_are_off(): void
    {
        Notification::fake();

        $tenant = $this->makeTenant();
        $this->makeUser($tenant, 'owner', 'online');

        $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        Notification::assertNothingSent();
    }

    public function test_online_agents_are_emailed_about_an_unassigned_chat(): void
    {
        Notification::fake();

        $tenant = $this->makeTenant();
        $this->configure($tenant, ['mail_enabled' => true]);

        $online = $this->makeUser($tenant, 'member', 'online');
        $offline = $this->makeUser($tenant, 'member', 'offline');

        $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        Notification::assertSentTo($online, ChatConversationStarted::class);
        Notification::assertNotSentTo($offline, ChatConversationStarted::class);
    }

    public function test_admins_are_emailed_when_nobody_is_online(): void
    {
        Notification::fake();

        $tenant = $this->makeTenant();
        $this->configure($tenant, ['mail_enabled' => true]);

        $owner = $this->makeUser($tenant, 'owner');
        $member = $this->makeUser($tenant, 'member');

        $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        // A chat arriving with nobody online is still somebody's problem.
        Notification::assertSentTo($owner, ChatConversationStarted::class);
        Notification::assertNotSentTo($member, ChatConversationStarted::class);
    }

    public function test_transferring_emails_the_receiving_agent_only(): void
    {
        Notification::fake();

        $tenant = $this->makeTenant();
        $this->configure($tenant, ['mail_enabled' => true]);

        $from = $this->makeUser($tenant);
        $to = $this->makeUser($tenant, 'member');
        $conversation = $this->makeConversation($tenant, $from);

        app(ConversationService::class)->transfer($conversation, $from, $to, 'Billing');

        Notification::assertSentTo($to, ChatConversationAssigned::class);
        Notification::assertNotSentTo($from, ChatConversationAssigned::class);
    }

    public function test_agents_from_another_tenant_are_never_notified(): void
    {
        Notification::fake();

        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $this->configure($tenantA, ['mail_enabled' => true]);
        $this->configure($tenantB, ['mail_enabled' => true]);

        $ours = $this->makeUser($tenantA, 'member', 'online');
        $theirs = $this->makeUser($tenantB, 'member', 'online');

        $this->postJson(route('chat.widget.start', $tenantA->slug), [])->assertOk();

        Notification::assertSentTo($ours, ChatConversationStarted::class);
        Notification::assertNotSentTo($theirs, ChatConversationStarted::class);
    }

    public function test_integration_settings_are_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $this->configure($tenantA, ['slack_webhook_url' => 'https://hooks.slack.test/acme']);

        $this->assertNull(app(IntegrationSettingsService::class)->for($tenantB)['slack_webhook_url']);

        // Only Acme's endpoint is ever called for Acme's traffic.
        Http::fake();
        (new SendChatAlert($tenantB->id, 'New chat', null))->handle(app(IntegrationSettingsService::class));
        Http::assertNothingSent();
    }

    public function test_settings_page_shows_the_integration_and_token_sections(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->assertSee('Notifications &amp; Webhooks', false)
            ->assertSee('API Tokens');
    }

    public function test_saving_integrations_keeps_values_that_were_not_submitted(): void
    {
        $tenant = $this->makeTenant();

        $this->configure($tenant, ['slack_webhook_url' => 'https://hooks.slack.test/keep-me']);
        $settings = $this->configure($tenant, ['discord_webhook_url' => 'https://discord.test/api/webhooks/new']);

        $this->assertEquals('https://hooks.slack.test/keep-me', $settings['slack_webhook_url']);
        $this->assertEquals('https://discord.test/api/webhooks/new', $settings['discord_webhook_url']);
    }

    public function test_an_alert_for_a_deleted_tenant_is_a_no_op(): void
    {
        Http::fake();

        (new SendChatAlert(999999, 'ghost', null))->handle(app(IntegrationSettingsService::class));
        (new SendChatWebhook(999999, 'conversation.created', []))->handle(app(IntegrationSettingsService::class));

        Http::assertNothingSent();
        $this->assertDatabaseCount('settings', 0);
        $this->assertTrue(true);
    }

    public function test_a_stored_setting_row_is_json(): void
    {
        $tenant = $this->makeTenant();
        $this->configure($tenant, ['slack_webhook_url' => 'https://hooks.slack.test/abc']);

        $raw = Setting::where('key', IntegrationSettingsService::SETTING_KEY)->firstOrFail()->value;

        $this->assertIsArray(json_decode($raw, true));
    }
}
