<?php

namespace Tests\Feature\Chat;

use App\Models\ChatArticle;
use App\Models\ChatCannedResponse;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatSchemaAndSeedTest extends TestCase
{
    use RefreshDatabase;

    // ─── Schema ───

    public function test_every_chat_table_exists(): void
    {
        foreach ([
            'chat_visitors',
            'chat_conversations',
            'chat_messages',
            'chat_attachments',
            'chat_canned_responses',
            'chat_articles',
            'chat_api_tokens',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_conversations_and_messages_carry_the_columns_the_module_relies_on(): void
    {
        $this->assertTrue(Schema::hasColumns('chat_conversations', [
            'tenant_id', 'chat_visitor_id', 'assigned_to', 'status', 'closed_at',
            'last_message_at', 'last_message_preview', 'deleted_at',
        ]));

        $this->assertTrue(Schema::hasColumns('chat_messages', [
            'tenant_id', 'chat_conversation_id', 'sender_type', 'sender_id',
            'body', 'is_internal', 'read_at',
        ]));
    }

    public function test_the_query_indexes_are_in_place(): void
    {
        $conversationIndexes = collect(Schema::getIndexes('chat_conversations'))->pluck('name');
        $messageIndexes = collect(Schema::getIndexes('chat_messages'))->pluck('name');

        $this->assertContains('chat_conversations_inbox_index', $conversationIndexes);
        $this->assertContains('chat_conversations_assignee_index', $conversationIndexes);
        $this->assertContains('chat_conversations_created_index', $conversationIndexes);

        $this->assertContains('chat_messages_unread_index', $messageIndexes);
        $this->assertContains('chat_messages_visible_index', $messageIndexes);
        $this->assertContains('chat_messages_created_index', $messageIndexes);
    }

    public function test_deleting_a_tenant_takes_its_chat_data_with_it(): void
    {
        $conversation = ChatConversation::factory()->create();
        ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id]);

        Tenant::findOrFail($conversation->tenant_id)->delete();

        $this->assertDatabaseCount('chat_conversations', 0);
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertDatabaseCount('chat_visitors', 0);
    }

    // ─── Factories ───

    public function test_a_conversation_factory_builds_a_complete_workspace(): void
    {
        $conversation = ChatConversation::factory()->create();

        $this->assertNotNull($conversation->tenant_id);
        // The visitor must belong to the same workspace as the conversation.
        $this->assertEquals(
            $conversation->tenant_id,
            ChatVisitor::withoutGlobalScopes()->findOrFail($conversation->chat_visitor_id)->tenant_id,
        );
        $this->assertTrue(Tenant::findOrFail($conversation->tenant_id)->isModuleEnabled('chat'));
    }

    public function test_a_conversation_can_be_placed_in_an_existing_workspace(): void
    {
        $tenant = Tenant::factory()->withChat()->create();
        $agent = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'status' => 'active']);

        $conversation = ChatConversation::factory()->assignedTo($agent)->create();

        $this->assertEquals($tenant->id, $conversation->tenant_id);
        $this->assertEquals($agent->id, $conversation->assigned_to);
    }

    public function test_the_message_factory_inherits_the_conversations_tenant(): void
    {
        $conversation = ChatConversation::factory()->create();

        $message = ChatMessage::factory()->create(['chat_conversation_id' => $conversation->id]);

        $this->assertEquals($conversation->tenant_id, $message->tenant_id);
        $this->assertTrue($message->isFromVisitor());
    }

    public function test_factory_states_produce_the_states_the_module_distinguishes(): void
    {
        $conversation = ChatConversation::factory()->closed()->create();
        $this->assertEquals('closed', $conversation->status);
        $this->assertNotNull($conversation->closed_at);

        $agent = User::factory()->create(['tenant_id' => $conversation->tenant_id, 'status' => 'active']);

        $note = ChatMessage::factory()
            ->internalNote($agent)
            ->create(['chat_conversation_id' => $conversation->id]);
        $this->assertTrue($note->is_internal);

        $this->assertFalse(ChatArticle::factory()->draft()->create()->is_published);
        $this->assertNull(ChatCannedResponse::factory()->withoutShortcut()->create()->shortcut);
        $this->assertNotNull(ChatVisitor::factory()->identified()->create()->email);
    }

    public function test_repeated_canned_response_factories_do_not_collide(): void
    {
        $tenant = Tenant::factory()->withChat()->create();

        // Shortcuts are unique per tenant in the schema.
        ChatCannedResponse::factory()->count(5)->create(['tenant_id' => $tenant->id]);

        $this->assertDatabaseCount('chat_canned_responses', 5);
    }

    // ─── Demo seed ───

    public function test_the_demo_seeder_produces_a_usable_chat_workspace(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::where('slug', 'demo-company')->firstOrFail();

        $this->assertTrue($tenant->isModuleEnabled('chat'));
        $this->assertEquals(3, $tenant->chatConversations()->withoutGlobalScopes()->count());
        $this->assertEquals(3, $tenant->chatArticles()->withoutGlobalScopes()->count());
        $this->assertEquals(3, $tenant->chatCannedResponses()->withoutGlobalScopes()->count());
    }

    public function test_the_seeded_inbox_renders_with_its_open_and_closed_chats(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(DemoSeeder::class);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@demo.com')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('chat.conversations.index'))->assertOk();

        // Two open chats; the resolved one is filtered out of the default view.
        $this->assertCount(2, $response->viewData('conversations'));

        $this->actingAs($admin)
            ->get(route('chat.conversations.index', ['status' => 'closed']))
            ->assertOk()
            ->assertSee('Tom Beckett');
    }

    public function test_the_seeded_waiting_chat_shows_as_unread_and_unassigned(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(DemoSeeder::class);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@demo.com')->firstOrFail();

        $response = $this->actingAs($admin)
            ->get(route('chat.conversations.index', ['filter' => 'unassigned']))
            ->assertOk();

        $waiting = $response->viewData('conversations')->first();

        $this->assertNull($waiting->assigned_to);
        $this->assertEquals(1, $waiting->unread_count);
    }

    public function test_the_seeded_internal_note_is_not_visible_to_a_visitor(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(DemoSeeder::class);

        $tenant = Tenant::where('slug', 'demo-company')->firstOrFail();

        $note = ChatMessage::withoutGlobalScopes()->where('is_internal', true)->firstOrFail();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        // The visitor's own thread is a different conversation, and the note is
        // excluded from every visitor-facing query regardless.
        $raw = $this->getJson(
            route('chat.widget.messages.index', [$tenant->slug, $start->json('conversation_id')])
            .'?visitor_token='.$start->json('visitor_token')
        )->assertOk()->getContent();

        $this->assertStringNotContainsString($note->body, $raw);
    }

    public function test_the_seeded_data_feeds_the_reports_screen(): void
    {
        $this->seed(PlanSeeder::class);
        $this->seed(ModuleSeeder::class);
        $this->seed(DemoSeeder::class);

        $admin = User::withoutGlobalScopes()->where('email', 'admin@demo.com')->firstOrFail();

        $summary = $this->actingAs($admin)
            ->get(route('chat.reports.index'))
            ->assertOk()
            ->viewData('summary');

        $this->assertEquals(3, $summary['conversations']);
        $this->assertEquals(1, $summary['closed']);
        $this->assertEquals(1, $summary['unanswered']);
        // The backdated agent replies give first-response and resolution figures.
        $this->assertNotNull($summary['avg_first_response_seconds']);
        $this->assertNotNull($summary['avg_resolution_seconds']);
    }
}
