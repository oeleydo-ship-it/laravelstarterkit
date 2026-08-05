<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatInboxSearchTest extends TestCase
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

    protected function makeConversation(Tenant $tenant, array $visitor = [], ?User $assignee = null): ChatConversation
    {
        $record = ChatVisitor::create(array_merge(['tenant_id' => $tenant->id], $visitor));

        return ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $record->id,
            'status' => 'open',
            'assigned_to' => $assignee?->id,
        ]);
    }

    protected function search(User $agent, array $params)
    {
        return $this->actingAs($agent)
            ->get(route('chat.conversations.index', $params))
            ->assertOk()
            ->viewData('conversations');
    }

    public function test_search_matches_the_visitor_name(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $ada = $this->makeConversation($tenant, ['name' => 'Ada Lovelace']);
        $this->makeConversation($tenant, ['name' => 'Grace Hopper']);

        $results = $this->search($agent, ['q' => 'Lovelace']);

        $this->assertCount(1, $results);
        $this->assertEquals($ada->id, $results->first()->id);
    }

    public function test_search_matches_the_visitor_email(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $target = $this->makeConversation($tenant, ['email' => 'billing@customer.test']);
        $this->makeConversation($tenant, ['email' => 'someone@else.test']);

        $results = $this->search($agent, ['q' => 'billing@customer']);

        $this->assertCount(1, $results);
        $this->assertEquals($target->id, $results->first()->id);
    }

    public function test_search_matches_message_bodies(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $target = $this->makeConversation($tenant);
        app(MessageService::class)->sendAsVisitor($target, 'My order arrived cracked down the middle.');

        $other = $this->makeConversation($tenant);
        app(MessageService::class)->sendAsVisitor($other, 'Where is my invoice?');

        $results = $this->search($agent, ['q' => 'cracked']);

        $this->assertCount(1, $results);
        $this->assertEquals($target->id, $results->first()->id);
    }

    public function test_search_matches_rating_feedback(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $target = ChatConversation::factory()
            ->rated(2, 'The agent was slow to reply')
            ->create(['tenant_id' => $tenant->id]);

        ChatConversation::factory()->rated(5, 'Perfect')->create(['tenant_id' => $tenant->id]);

        $results = $this->search($agent, ['status' => 'closed', 'q' => 'slow to reply']);

        $this->assertCount(1, $results);
        $this->assertEquals($target->id, $results->first()->id);
    }

    public function test_search_finds_internal_notes_which_are_staff_facing_anyway(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $target = $this->makeConversation($tenant, [], $agent);
        app(MessageService::class)->addInternalNote($target, $agent, 'Escalated to the finance team');

        $results = $this->search($agent, ['q' => 'finance team']);

        $this->assertCount(1, $results);
        $this->assertEquals($target->id, $results->first()->id);
    }

    public function test_search_never_reaches_into_another_workspace(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $foreign = $this->makeConversation($tenantB, ['name' => 'Ada Lovelace']);
        app(MessageService::class)->sendAsVisitor($foreign, 'globex confidential detail');

        $agentA = $this->makeUser($tenantA);

        $this->assertCount(0, $this->search($agentA, ['q' => 'Lovelace']));
        $this->assertCount(0, $this->search($agentA, ['q' => 'confidential']));
    }

    public function test_search_combines_with_the_status_and_assignment_filters(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $colleague = $this->makeUser($tenant, 'member');

        $mine = $this->makeConversation($tenant, ['name' => 'Ada Lovelace'], $agent);
        $theirs = $this->makeConversation($tenant, ['name' => 'Ada Byron'], $colleague);

        $results = $this->search($agent, ['filter' => 'mine', 'q' => 'Ada']);

        $this->assertCount(1, $results);
        $this->assertEquals($mine->id, $results->first()->id);
        $this->assertNotEquals($theirs->id, $results->first()->id);
    }

    public function test_a_wildcard_in_the_term_is_treated_as_literal_text(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $this->makeConversation($tenant, ['name' => 'Ada Lovelace']);
        $literal = $this->makeConversation($tenant, ['name' => 'Discount 100% off']);

        // An unescaped % would match every visitor instead of just this one.
        $results = $this->search($agent, ['q' => '100%']);

        $this->assertCount(1, $results);
        $this->assertEquals($literal->id, $results->first()->id);
    }

    public function test_an_empty_search_lists_everything_as_before(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $this->makeConversation($tenant, ['name' => 'Ada']);
        $this->makeConversation($tenant, ['name' => 'Grace']);

        $this->assertCount(2, $this->search($agent, ['q' => '']));
        $this->assertCount(2, $this->search($agent, []));
    }

    public function test_a_search_with_no_matches_says_so(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $this->makeConversation($tenant, ['name' => 'Ada']);

        $this->actingAs($agent)
            ->get(route('chat.conversations.index', ['q' => 'nothing-matches-this']))
            ->assertOk()
            ->assertSee('No conversations match that search.');
    }

    public function test_the_search_term_survives_a_filter_change(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        $this->makeConversation($tenant, ['name' => 'Ada']);

        // The filter links must carry ?q, or narrowing silently discards the
        // search the agent just typed.
        $this->actingAs($agent)
            ->get(route('chat.conversations.index', ['q' => 'Ada']))
            ->assertOk()
            // Escaped: the query separator renders as &amp; in the href.
            ->assertSee(route('chat.conversations.index', ['filter' => 'unassigned', 'q' => 'Ada']));
    }
}
