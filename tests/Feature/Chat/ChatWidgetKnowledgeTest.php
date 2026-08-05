<?php

namespace Tests\Feature\Chat;

use App\Models\ChatArticle;
use App\Models\ChatConversation;
use App\Models\ChatDocument;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatWidgetKnowledgeTest extends TestCase
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

    public function test_widget_knowledge_endpoint_returns_published_articles(): void
    {
        $tenant = $this->makeTenant();

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Refund policy',
            'body' => 'Refunds are processed within five business days after approval.',
            'is_published' => true,
        ]);

        ChatArticle::create([
            'tenant_id' => $tenant->id,
            'title' => 'Draft only',
            'body' => 'Should not appear in the widget help center.',
            'is_published' => false,
        ]);

        ChatDocument::create([
            'tenant_id' => $tenant->id,
            'title' => 'Shipping guide',
            'original_name' => 'shipping.txt',
            'disk' => 'local',
            'path' => 'chat/1/knowledge/shipping.txt',
            'size' => 80,
            'extracted_text' => 'We ship worldwide with tracked delivery in 3-5 days.',
            'is_active' => true,
        ]);

        $this->getJson(route('chat.widget.knowledge', $tenant->slug))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Refund policy'])
            ->assertJsonFragment(['title' => 'Shipping guide'])
            ->assertJsonMissing(['title' => 'Draft only']);
    }

    public function test_force_new_starts_a_fresh_conversation(): void
    {
        $tenant = $this->makeTenant();
        $visitor = ChatVisitor::create(['tenant_id' => $tenant->id]);

        $first = $this->postJson(route('chat.widget.start', $tenant->slug), [
            'visitor_token' => $visitor->token,
        ])->assertOk()->json('conversation_id');

        $second = $this->postJson(route('chat.widget.start', $tenant->slug), [
            'visitor_token' => $visitor->token,
            'force_new' => true,
        ])->assertOk()->json('conversation_id');

        $this->assertNotEquals($first, $second);
        $this->assertEquals('closed', ChatConversation::find($first)->status);
        $this->assertEquals('open', ChatConversation::find($second)->status);
    }
}
