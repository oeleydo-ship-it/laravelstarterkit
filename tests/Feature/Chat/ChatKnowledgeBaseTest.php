<?php

namespace Tests\Feature\Chat;

use App\Models\ChatArticle;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\Ai\AiProvider;
use App\Services\Chat\AiAssistService;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Records what it was asked so the tests can assert on the prompt, and never
 * makes a network call.
 */
class FakeAiProvider implements AiProvider
{
    public ?string $system = null;

    public array $messages = [];

    public function __construct(
        public bool $configured = true,
        public string $reply = 'Happy to help — your refund lands in 5 days.',
        public ?string $failWith = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function complete(string $system, array $messages): string
    {
        $this->system = $system;
        $this->messages = $messages;

        if ($this->failWith) {
            throw new RuntimeException($this->failWith);
        }

        return $this->reply;
    }
}

class ChatKnowledgeBaseTest extends TestCase
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

    protected function fakeProvider(FakeAiProvider $provider): FakeAiProvider
    {
        $this->app->instance(AiProvider::class, $provider);

        $settings = \Mockery::mock(\App\Services\Chat\AiSettingsService::class)->shouldIgnoreMissing();
        $settings->shouldReceive('makeProvider')->andReturn($provider);
        $this->app->instance(\App\Services\Chat\AiSettingsService::class, $settings);

        return $provider;
    }

    // ─── Knowledge base CRUD ───

    public function test_admin_can_create_an_article(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->post(route('chat.articles.store'), [
                'title' => 'Refund policy',
                'keywords' => 'refund, money back',
                'body' => 'Refunds are processed within 5 working days.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('chat.articles.index'));

        $this->assertDatabaseHas('chat_articles', [
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'is_published' => true,
        ]);
    }

    public function test_an_article_saved_without_the_publish_switch_is_a_draft(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->post(route('chat.articles.store'), [
                'title' => 'Work in progress',
                'body' => 'Not ready yet.',
            ])
            ->assertRedirect();

        $this->assertFalse(ChatArticle::withoutGlobalScopes()->firstOrFail()->is_published);
    }

    public function test_members_cannot_manage_articles_but_can_read_them(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Shipping times',
            'body' => 'We ship in 2 days.',
        ]);

        $this->actingAs($member)
            ->post(route('chat.articles.store'), ['title' => 'Nope', 'body' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($member)
            ->get(route('chat.articles.index'))
            ->assertOk()
            ->assertSee('Shipping times');
    }

    public function test_articles_are_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = ChatArticle::create([
            'tenant_id' => $tenantB->id,
            'title' => 'Globex internal pricing',
            'body' => 'Floor price is $40.',
        ]);

        $agentA = $this->makeUser($tenantA);

        $this->actingAs($agentA)
            ->get(route('chat.articles.index'))
            ->assertOk()
            ->assertDontSee('Globex internal pricing');

        $this->actingAs($agentA)
            ->get(route('chat.articles.edit', $foreign))
            ->assertNotFound();
    }

    public function test_search_matches_title_keywords_and_body(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Returns',
            'keywords' => 'refund',
            'body' => 'Send it back within 30 days.',
        ]);
        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Delivery',
            'body' => 'Couriers deliver on weekdays.',
        ]);

        $this->actingAs($agent);

        $this->getJson(route('chat.articles.search', ['q' => 'refund']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Returns');

        $this->getJson(route('chat.articles.search', ['q' => 'courier']))
            ->assertOk()
            ->assertJsonPath('0.title', 'Delivery');
    }

    public function test_a_wildcard_in_the_term_is_matched_literally(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Delivery',
            'body' => 'Couriers deliver on weekdays.',
        ]);
        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Discounts',
            'body' => 'Members get 100% off their first month.',
        ]);

        // Escaping alone is not enough — without an ESCAPE clause SQLite reads
        // the backslash literally and matches nothing at all.
        $this->actingAs($agent)
            ->getJson(route('chat.articles.search', ['q' => '100%']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Discounts');
    }

    public function test_composer_search_never_returns_drafts(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Unfinished refund policy',
            'body' => 'Draft text about refunds.',
            'is_published' => false,
        ]);

        $this->actingAs($agent)
            ->getJson(route('chat.articles.search', ['q' => 'refund']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_composer_search_is_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        ChatArticle::create([
            'tenant_id' => $tenantB->id,
            'title' => 'Globex refund secrets',
            'body' => 'Refunds are always approved.',
        ]);

        $this->actingAs($this->makeUser($tenantA))
            ->getJson(route('chat.articles.search', ['q' => 'refund']))
            ->assertOk()
            ->assertJsonCount(0);
    }

    // ─── AI assist ───

    public function test_assist_is_unavailable_by_default(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->assertFalse(app(AiAssistService::class)->isAvailable());

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.suggest', $conversation))
            ->assertStatus(503);
    }

    public function test_the_suggest_button_is_hidden_when_assist_is_unconfigured(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertDontSee('Suggest reply');

        $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Suggest reply');
    }

    public function test_agent_can_request_a_draft_grounded_in_the_knowledge_base(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'body' => 'Refunds are processed within 5 working days.',
        ]);

        app(MessageService::class)->sendAsVisitor($conversation, 'How long do refunds take?');

        $provider = $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.suggest', $conversation))
            ->assertOk()
            ->assertJsonPath('suggestion', 'Happy to help — your refund lands in 5 days.');

        $this->assertStringContainsString('Refunds are processed within 5 working days.', $provider->system);
        $this->assertEquals('user', $provider->messages[0]['role']);
        $this->assertEquals('How long do refunds take?', $provider->messages[0]['content']);
    }

    public function test_a_draft_is_never_sent_to_the_visitor(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'Hi?');
        $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.suggest', $conversation))
            ->assertOk();

        // Only the visitor's own message exists — the draft was returned, not sent.
        $this->assertEquals(1, $conversation->messages()->count());
    }

    public function test_internal_notes_are_kept_out_of_the_ai_context(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'Can I get a discount?');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'This customer is on the churn list.');

        $provider = $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($agent)->postJson(route('chat.conversations.suggest', $conversation))->assertOk();

        $sent = json_encode($provider->messages);
        $this->assertStringNotContainsString('churn list', $sent);
        $this->assertStringNotContainsString('churn list', $provider->system);
    }

    public function test_only_the_workspaces_own_articles_reach_the_provider(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        ChatArticle::create([
            'tenant_id' => $tenantB->id,
            'title' => 'Globex refund secrets',
            'body' => 'Globex approves every refund immediately.',
        ]);

        $agent = $this->makeUser($tenantA);
        $conversation = $this->makeConversation($tenantA, $agent);
        app(MessageService::class)->sendAsVisitor($conversation, 'Question about a refund please');

        $provider = $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($agent)->postJson(route('chat.conversations.suggest', $conversation))->assertOk();

        $this->assertStringNotContainsString('Globex', $provider->system);
    }

    public function test_a_provider_failure_is_reported_without_leaking_its_message(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($conversation, 'Hello?');

        $this->fakeProvider(new FakeAiProvider(failWith: 'billing account 4242 is past due'));

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.suggest', $conversation))
            ->assertStatus(502)
            ->assertJsonMissing(['message' => 'billing account 4242 is past due']);
    }

    public function test_an_agent_cannot_request_a_draft_for_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $conversation = $this->makeConversation($tenantA);
        $this->fakeProvider(new FakeAiProvider);

        $this->actingAs($this->makeUser($tenantB))
            ->postJson(route('chat.conversations.suggest', $conversation))
            ->assertForbidden();
    }

    public function test_the_default_provider_binding_is_the_null_provider(): void
    {
        config(['chat.ai.provider' => 'not-a-real-provider']);

        $this->assertFalse(app(AiProvider::class)->isConfigured());
    }
}
