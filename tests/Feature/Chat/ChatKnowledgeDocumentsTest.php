<?php

namespace Tests\Feature\Chat;

use App\Models\ChatConversation;
use App\Models\ChatDocument;
use App\Models\ChatVisitor;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\KnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatKnowledgeDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme']);

        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'chat',
            'enabled' => true,
        ]);

        return $tenant;
    }

    protected function makeAdmin(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_upload_a_knowledge_document_in_settings(): void
    {
        Storage::fake('local');
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $file = UploadedFile::fake()->createWithContent(
            'refunds.txt',
            'Refunds are processed within five business days.'
        );

        $this->actingAs($admin)
            ->post(route('chat.settings.documents.store'), [
                'title' => 'Refund policy',
                'document' => $file,
            ])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'knowledge']));

        $document = ChatDocument::first();
        $this->assertNotNull($document);
        $this->assertEquals('Refund policy', $document->title);
        $this->assertStringContainsString('five business days', $document->extracted_text);
        Storage::disk($document->disk)->assertExists($document->path);
    }

    public function test_composer_search_returns_documents(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Shipping guide',
            'original_name' => 'shipping.txt',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/shipping.txt',
            'size' => 100,
            'extracted_text' => 'We ship worldwide with tracked delivery.',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->getJson(route('chat.articles.search', ['q' => 'shipping']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Shipping guide (document)']);
    }

    public function test_visitor_receives_auto_reply_from_knowledge_base(): void
    {
        $tenant = $this->makeTenant();
        Setting::set(KnowledgeBaseService::AUTO_REPLY_SETTING, '1', $tenant->id);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'original_name' => 'refunds.txt',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/refunds.txt',
            'size' => 120,
            'extracted_text' => 'Refunds are processed within five business days after approval.',
            'is_active' => true,
        ]);

        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);
        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
        ]);

        $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversation->id]), [
            'visitor_token' => $visitor->token,
            'body' => 'How long do refunds take?',
        ])->assertCreated();

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'bot',
        ]);

        $bot = $conversation->messages()->where('sender_type', 'bot')->first();
        $this->assertStringContainsString('Refund policy', $bot->body);
    }

    public function test_visitor_receives_ai_reply_when_no_agent_is_assigned(): void
    {
        $tenant = $this->makeTenant();
        Setting::set(KnowledgeBaseService::AUTO_REPLY_SETTING, '1', $tenant->id);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'original_name' => 'refunds.txt',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/refunds.txt',
            'size' => 120,
            'extracted_text' => 'Refunds are processed within five business days after approval.',
            'is_active' => true,
        ]);

        $fake = new \Tests\Feature\Chat\FakeAiProvider(reply: 'Refunds usually take five business days.');
        $this->app->instance(\App\Services\Chat\Ai\AiProvider::class, $fake);
        $settings = \Mockery::mock(\App\Services\Chat\AiSettingsService::class)->shouldIgnoreMissing();
        $settings->shouldReceive('makeProvider')->andReturn($fake);
        $this->app->instance(\App\Services\Chat\AiSettingsService::class, $settings);

        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);
        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
        ]);

        $response = $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversation->id]), [
            'visitor_token' => $visitor->token,
            'body' => 'How long do refunds take?',
        ])->assertCreated();

        $response->assertJsonPath('bot_reply.body', 'Refunds usually take five business days.');
        $response->assertJsonPath('bot_reply.sender_type', 'bot');

        $this->assertDatabaseHas('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'bot',
            'body' => 'Refunds usually take five business days.',
        ]);
    }

    public function test_unreadable_document_text_is_not_shown_to_visitors(): void
    {
        $tenant = $this->makeTenant();
        Setting::set(KnowledgeBaseService::AUTO_REPLY_SETTING, '1', $tenant->id);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Broken PDF scrape',
            'original_name' => 'scan.pdf',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/scan.pdf',
            'size' => 120,
            'extracted_text' => "dfbj????[??]\x00\x01\x02%%%@@@###",
            'is_active' => true,
        ]);

        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);
        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
        ]);

        $response = $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversation->id]), [
            'visitor_token' => $visitor->token,
            'body' => 'hello there friend',
        ])->assertCreated();

        $botBody = $response->json('bot_reply.body');
        $this->assertNotNull($botBody);
        $this->assertStringNotContainsString('dfbj', $botBody);
        $this->assertStringContainsString('live agent', strtolower($botBody));
    }

    public function test_auto_reply_skips_when_agent_has_accepted(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeAdmin($tenant);
        Setting::set(KnowledgeBaseService::AUTO_REPLY_SETTING, '1', $tenant->id);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'original_name' => 'refunds.txt',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/refunds.txt',
            'size' => 120,
            'extracted_text' => 'Refunds are processed within five business days after approval.',
            'is_active' => true,
        ]);

        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);
        $conversation = ChatConversation::create([
            'tenant_id' => $tenant->id,
            'chat_visitor_id' => $visitor->id,
            'status' => 'open',
            'assigned_to' => $agent->id,
        ]);

        $this->postJson(route('chat.widget.messages.store', [$tenant->slug, $conversation->id]), [
            'visitor_token' => $visitor->token,
            'body' => 'How long do refunds take?',
        ])->assertCreated();

        $this->assertDatabaseMissing('chat_messages', [
            'chat_conversation_id' => $conversation->id,
            'sender_type' => 'bot',
        ]);
    }
}
