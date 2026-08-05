<?php

namespace Tests\Feature\Chat;

use App\Jobs\SendChatAlert;
use App\Jobs\SendChatWebhook;
use App\Models\ChatConversation;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ChatRatingTest extends TestCase
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

    /**
     * @return array{0: Tenant, 1: string, 2: ChatConversation}
     */
    protected function closedChat(string $slug = 'acme'): array
    {
        $tenant = $this->makeTenant($slug);

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($start->json('conversation_id'));

        app(ConversationService::class)->close($conversation);

        return [$tenant, $start->json('visitor_token'), $conversation->fresh()];
    }

    protected function rate(Tenant $tenant, ChatConversation $conversation, array $payload)
    {
        return $this->postJson(
            route('chat.widget.rating.store', [$tenant->slug, $conversation->id]),
            $payload,
        );
    }

    // ─── Recording a rating ───

    public function test_a_visitor_can_rate_a_closed_chat(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        $this->rate($tenant, $conversation, [
            'rating' => 4,
            'comment' => 'Quick and helpful.',
            'visitor_token' => $token,
        ])->assertCreated()->assertJsonPath('rating', 4);

        $conversation->refresh();

        $this->assertEquals(4, $conversation->rating);
        $this->assertEquals('Quick and helpful.', $conversation->rating_comment);
        $this->assertNotNull($conversation->rated_at);
        $this->assertTrue($conversation->isRated());
    }

    public function test_a_comment_is_optional(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        $this->rate($tenant, $conversation, ['rating' => 5, 'visitor_token' => $token])->assertCreated();

        $this->assertNull($conversation->fresh()->rating_comment);
    }

    public function test_an_open_chat_cannot_be_rated(): void
    {
        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $conversation = ChatConversation::withoutGlobalScopes()->findOrFail($start->json('conversation_id'));

        // Scoring an unfinished job is not feedback anyone can act on.
        $this->rate($tenant, $conversation, [
            'rating' => 5,
            'visitor_token' => $start->json('visitor_token'),
        ])->assertStatus(422);

        $this->assertFalse($conversation->fresh()->isRated());
    }

    public function test_a_chat_can_only_be_rated_once(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        $this->rate($tenant, $conversation, [
            'rating' => 5,
            'comment' => 'The considered version.',
            'visitor_token' => $token,
        ])->assertCreated();

        $this->rate($tenant, $conversation, [
            'rating' => 1,
            'comment' => 'The stray second click.',
            'visitor_token' => $token,
        ])->assertStatus(409);

        $conversation->refresh();

        $this->assertEquals(5, $conversation->rating);
        $this->assertEquals('The considered version.', $conversation->rating_comment);
    }

    public function test_the_score_must_be_within_the_scale(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        foreach ([0, 6, -1] as $invalid) {
            $this->rate($tenant, $conversation, ['rating' => $invalid, 'visitor_token' => $token])
                ->assertStatus(422)
                ->assertJsonValidationErrors('rating');
        }

        $this->assertFalse($conversation->fresh()->isRated());
    }

    public function test_rating_requires_the_owning_visitors_token(): void
    {
        [$tenant, , $conversation] = $this->closedChat();

        $this->rate($tenant, $conversation, ['rating' => 1, 'visitor_token' => 'wrong-token'])
            ->assertForbidden();

        $this->assertFalse($conversation->fresh()->isRated());
    }

    public function test_a_visitor_cannot_rate_another_workspaces_chat(): void
    {
        [, $tokenA] = $this->closedChat('acme');
        [$tenantB, , $conversationB] = $this->closedChat('globex');

        $this->rate($tenantB, $conversationB, ['rating' => 1, 'visitor_token' => $tokenA])
            ->assertForbidden();

        $this->assertFalse($conversationB->fresh()->isRated());
    }

    // ─── Downstream effects ───

    public function test_a_rating_alerts_the_team_and_fires_a_webhook(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        Bus::fake();

        $this->rate($tenant, $conversation, [
            'rating' => 2,
            'comment' => 'Took a while.',
            'visitor_token' => $token,
        ])->assertCreated();

        Bus::assertDispatched(SendChatAlert::class, fn (SendChatAlert $job) => str_contains($job->text, '2/5')
            && str_contains($job->text, 'Took a while.'));

        Bus::assertDispatched(SendChatWebhook::class, fn (SendChatWebhook $job) => $job->event === 'conversation.rated'
            && $job->payload['rating'] === 2);
    }

    public function test_the_rating_shows_in_the_inbox_and_on_the_conversation(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();

        $this->rate($tenant, $conversation, [
            'rating' => 3,
            'comment' => 'Middling but fine.',
            'visitor_token' => $token,
        ])->assertCreated();

        $agent = $this->makeUser($tenant);

        $this->actingAs($agent)
            ->get(route('chat.conversations.index', ['status' => 'closed']))
            ->assertOk()
            ->assertSee('★★★☆☆');

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Middling but fine.');
    }

    public function test_the_rated_filter_shows_only_scored_chats(): void
    {
        [$tenant, $token, $rated] = $this->closedChat();
        $this->rate($tenant, $rated, ['rating' => 5, 'visitor_token' => $token])->assertCreated();

        // A second closed chat that nobody rated.
        $second = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $unrated = ChatConversation::withoutGlobalScopes()->findOrFail($second->json('conversation_id'));
        app(ConversationService::class)->close($unrated);

        $agent = $this->makeUser($tenant);

        $listed = $this->actingAs($agent)
            ->get(route('chat.conversations.index', ['status' => 'closed', 'filter' => 'rated']))
            ->assertOk()
            ->viewData('conversations');

        $this->assertCount(1, $listed);
        $this->assertEquals($rated->id, $listed->first()->id);
    }

    public function test_ratings_feed_the_reports(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();
        $this->rate($tenant, $conversation, ['rating' => 4, 'visitor_token' => $token])->assertCreated();

        $second = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $other = ChatConversation::withoutGlobalScopes()->findOrFail($second->json('conversation_id'));
        app(ConversationService::class)->close($other);
        $this->rate($tenant, $other, ['rating' => 2, 'visitor_token' => $second->json('visitor_token')])
            ->assertCreated();

        $admin = $this->makeUser($tenant, 'admin');

        $summary = $this->actingAs($admin)
            ->get(route('chat.reports.index'))
            ->assertOk()
            ->viewData('summary');

        $this->assertEquals(2, $summary['rated']);
        $this->assertEquals(3.0, $summary['avg_rating']);
        $this->assertEquals(100.0, $summary['rating_response_rate']);
    }

    public function test_an_unrated_range_reports_no_average_rather_than_zero(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        ChatConversation::factory()->closed()->create(['tenant_id' => $tenant->id]);

        $summary = $this->actingAs($admin)
            ->get(route('chat.reports.index'))
            ->assertOk()
            ->viewData('summary');

        // Zero is not a score this scale allows — "no data" must stay distinct.
        $this->assertNull($summary['avg_rating']);
        $this->assertEquals(0.0, $summary['rating_response_rate']);
        $this->assertEquals(0, $summary['rated']);
    }

    public function test_the_response_rate_is_null_when_nothing_was_closed(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        ChatConversation::factory()->create(['tenant_id' => $tenant->id]);

        $summary = $this->actingAs($admin)->get(route('chat.reports.index'))->assertOk()->viewData('summary');

        $this->assertNull($summary['rating_response_rate']);
    }

    public function test_per_agent_ratings_are_attributed_to_the_assignee(): void
    {
        $tenant = $this->makeTenant();
        $alice = $this->makeUser($tenant);
        $bob = $this->makeUser($tenant, 'member');
        $admin = $this->makeUser($tenant, 'admin');

        ChatConversation::factory()->rated(5)->create(['tenant_id' => $tenant->id, 'assigned_to' => $alice->id]);
        ChatConversation::factory()->rated(3)->create(['tenant_id' => $tenant->id, 'assigned_to' => $alice->id]);
        ChatConversation::factory()->rated(1)->create(['tenant_id' => $tenant->id, 'assigned_to' => $bob->id]);

        $rows = $this->actingAs($admin)
            ->get(route('chat.reports.index'))
            ->assertOk()
            ->viewData('perAgent')
            ->keyBy('agent');

        $this->assertEquals(4.0, $rows[$alice->name]['avg_rating']);
        $this->assertEquals(2, $rows[$alice->name]['rated']);
        $this->assertEquals(1.0, $rows[$bob->name]['avg_rating']);
        $this->assertNull($rows[$admin->name]['avg_rating']);
    }

    public function test_the_export_carries_the_rating_and_feedback(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();
        $this->rate($tenant, $conversation, [
            'rating' => 5,
            'comment' => 'Exported feedback line.',
            'visitor_token' => $token,
        ])->assertCreated();

        $admin = $this->makeUser($tenant, 'admin');

        $csv = $this->actingAs($admin)->get(route('chat.reports.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Rating,Feedback', $csv);
        $this->assertStringContainsString('Exported feedback line.', $csv);
    }

    public function test_the_rest_api_exposes_the_rating(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();
        $this->rate($tenant, $conversation, [
            'rating' => 5,
            'comment' => 'Via the API.',
            'visitor_token' => $token,
        ])->assertCreated();

        [, $apiToken] = app(\App\Services\Chat\ApiTokenService::class)->issue($tenant, 'Test');

        $this->getJson(route('api.chat.conversations.show', $conversation), [
            'Authorization' => "Bearer {$apiToken}",
        ])
            ->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.rating_comment', 'Via the API.');
    }

    public function test_reopening_a_rated_chat_keeps_the_score(): void
    {
        [$tenant, $token, $conversation] = $this->closedChat();
        $this->rate($tenant, $conversation, ['rating' => 4, 'visitor_token' => $token])->assertCreated();

        app(ConversationService::class)->reopen($conversation->fresh());

        // The score describes the exchange that happened, not the current state.
        $this->assertEquals(4, $conversation->fresh()->rating);
        $this->assertTrue($conversation->fresh()->isRated());
    }
}
