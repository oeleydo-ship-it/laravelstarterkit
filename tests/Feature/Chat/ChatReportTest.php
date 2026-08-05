<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Services\Chat\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatReportTest extends TestCase
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

    protected function range(): array
    {
        return [now()->subDays(30)->startOfDay(), now()->endOfDay()];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_summary_counts_conversations_and_messages(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        $one = $this->makeConversation($tenant, $agent);
        $two = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor($one, 'hello');
        app(MessageService::class)->sendAsAgent($one, $agent, 'hi there');
        app(MessageService::class)->addInternalNote($one, $agent, 'known customer');
        app(MessageService::class)->sendAsVisitor($two, 'anyone?');

        [$from, $to] = $this->range();
        $summary = app(ReportService::class)->summary($from, $to);

        $this->assertEquals(2, $summary['conversations']);
        $this->assertEquals(2, $summary['visitor_messages']);
        $this->assertEquals(1, $summary['agent_messages']);
        $this->assertEquals(1, $summary['internal_notes']);
        // Only the second chat never got a reply.
        $this->assertEquals(1, $summary['unanswered']);
    }

    public function test_first_response_time_is_measured_from_the_conversation_start(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        Carbon::setTestNow(Carbon::parse('2026-08-04 10:00:00'));
        $conversation = $this->makeConversation($tenant, $agent);
        app(MessageService::class)->sendAsVisitor($conversation, 'help');

        Carbon::setTestNow(Carbon::parse('2026-08-04 10:02:00'));
        app(MessageService::class)->sendAsAgent($conversation, $agent, 'on it');

        Carbon::setTestNow(Carbon::parse('2026-08-04 12:00:00'));

        $summary = app(ReportService::class)->summary(...$this->range());

        $this->assertEquals(120, $summary['avg_first_response_seconds']);
        $this->assertEquals(120, $summary['median_first_response_seconds']);
    }

    public function test_internal_notes_do_not_count_as_a_first_response(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        $conversation = $this->makeConversation($tenant, $agent);
        app(MessageService::class)->sendAsVisitor($conversation, 'help');
        app(MessageService::class)->addInternalNote($conversation, $agent, 'looking into it');

        $summary = app(ReportService::class)->summary(...$this->range());

        $this->assertEquals(1, $summary['unanswered']);
        $this->assertNull($summary['avg_first_response_seconds']);
    }

    public function test_resolution_time_uses_the_moment_the_chat_was_closed(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        Carbon::setTestNow(Carbon::parse('2026-08-04 09:00:00'));
        $conversation = $this->makeConversation($tenant, $agent);

        Carbon::setTestNow(Carbon::parse('2026-08-04 09:30:00'));
        app(ConversationService::class)->close($conversation);

        Carbon::setTestNow(Carbon::parse('2026-08-04 15:00:00'));
        // A later edit must not move the measured resolution time.
        $conversation->fresh()->update(['last_message_preview' => 'touched later']);

        $summary = app(ReportService::class)->summary(...$this->range());

        $this->assertEquals(1800, $summary['avg_resolution_seconds']);
        $this->assertEquals(1, $summary['closed']);
    }

    public function test_reopening_clears_the_closed_timestamp(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        $conversation = $this->makeConversation($tenant, $agent);
        app(ConversationService::class)->close($conversation);
        app(ConversationService::class)->reopen($conversation);

        $this->assertNull($conversation->fresh()->closed_at);

        $summary = app(ReportService::class)->summary(...$this->range());
        $this->assertNull($summary['avg_resolution_seconds']);
        $this->assertEquals(1, $summary['open']);
    }

    public function test_conversations_outside_the_range_are_excluded(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        Carbon::setTestNow(Carbon::parse('2026-01-01 09:00:00'));
        $this->makeConversation($tenant, $agent);

        Carbon::setTestNow(Carbon::parse('2026-08-04 09:00:00'));
        $this->makeConversation($tenant, $agent);

        $summary = app(ReportService::class)->summary(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-31')->endOfDay(),
        );

        $this->assertEquals(1, $summary['conversations']);
    }

    public function test_reports_never_include_another_tenants_traffic(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $agentB = $this->makeUser($tenantB);
        $foreign = $this->makeConversation($tenantB, $agentB);
        app(MessageService::class)->sendAsVisitor($foreign, 'globex traffic');

        $adminA = $this->makeUser($tenantA, 'admin');
        $this->makeConversation($tenantA, $adminA);

        // Through the route, so the tenant is bound the same way it is in
        // production — that binding is what the models' global scope reads.
        $response = $this->actingAs($adminA)->get(route('chat.reports.index'))->assertOk();

        $this->assertEquals(1, $response->viewData('summary')['conversations']);
        $this->assertEquals(0, $response->viewData('summary')['visitor_messages']);

        $agents = $response->viewData('perAgent')->pluck('agent');
        $this->assertContains($adminA->name, $agents);
        $this->assertNotContains($agentB->name, $agents);
    }

    public function test_per_agent_breakdown_attributes_work_to_the_right_agent(): void
    {
        $tenant = $this->makeTenant();
        $alice = $this->makeUser($tenant);
        $bob = $this->makeUser($tenant, 'member');
        $this->actingAs($alice);

        $aliceChat = $this->makeConversation($tenant, $alice);
        app(MessageService::class)->sendAsAgent($aliceChat, $alice, 'alice replying');
        app(MessageService::class)->sendAsAgent($aliceChat, $alice, 'again');

        $bobChat = $this->makeConversation($tenant, $bob);
        app(MessageService::class)->sendAsAgent($bobChat, $bob, 'bob replying');

        $rows = app(ReportService::class)->perAgent(...$this->range())->keyBy('agent');

        $this->assertEquals(2, $rows[$alice->name]['replies']);
        $this->assertEquals(1, $rows[$alice->name]['conversations']);
        $this->assertEquals(1, $rows[$bob->name]['replies']);
    }

    public function test_daily_rows_cover_every_day_in_the_range_including_empty_ones(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $this->actingAs($agent);

        $daily = app(ReportService::class)->daily(
            Carbon::parse('2026-08-01')->startOfDay(),
            Carbon::parse('2026-08-07')->endOfDay(),
        );

        $this->assertCount(7, $daily);
        $this->assertEquals('2026-08-01', $daily->first()['date']);
        $this->assertEquals(0, $daily->first()['conversations']);
    }

    // ─── HTTP ───

    public function test_admin_can_view_the_reports_screen(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->get(route('chat.reports.index'))
            ->assertOk()
            ->assertSee('Chat Reports')
            ->assertSee('Avg first response');
    }

    public function test_members_cannot_view_reports(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->get(route('chat.reports.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($member)
            ->get(route('chat.reports.export'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_conversation_export_streams_a_csv(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');
        $this->actingAs($admin);

        $conversation = $this->makeConversation($tenant, $admin);
        app(MessageService::class)->sendAsVisitor($conversation, 'export me please');

        $response = $this->get(route('chat.reports.export'))->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('ID,Visitor,Email,Status', $csv);
        $this->assertStringContainsString('export me please', $csv);
        $this->assertStringContainsString($admin->name, $csv);
    }

    public function test_agent_export_streams_a_csv(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');
        $this->actingAs($admin);

        $csv = $this->get(route('chat.reports.export', ['type' => 'agents']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Agent,Conversations,Closed,Replies', $csv);
        $this->assertStringContainsString($admin->name, $csv);
    }

    public function test_export_never_includes_another_tenants_conversations(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $agentB = $this->makeUser($tenantB);
        $foreign = $this->makeConversation($tenantB, $agentB);
        app(MessageService::class)->sendAsVisitor($foreign, 'globex confidential');

        $adminA = $this->makeUser($tenantA, 'admin');

        $csv = $this->actingAs($adminA)->get(route('chat.reports.export'))->assertOk()->streamedContent();

        $this->assertStringNotContainsString('globex confidential', $csv);
    }

    public function test_a_reversed_date_range_is_swapped_rather_than_reporting_nothing(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');
        $this->actingAs($admin);

        $this->makeConversation($tenant, $admin);

        $response = $this->get(route('chat.reports.index', [
            'from' => now()->addDay()->toDateString(),
            'to' => now()->subDays(7)->toDateString(),
        ]))->assertOk();

        $this->assertEquals(1, $response->viewData('summary')['conversations']);
    }

    public function test_human_duration_formats_each_scale(): void
    {
        $this->assertEquals('—', ReportService::humanDuration(null));
        $this->assertEquals('45s', ReportService::humanDuration(45));
        $this->assertEquals('2m 5s', ReportService::humanDuration(125));
        $this->assertEquals('1h 1m', ReportService::humanDuration(3660));
    }
}
