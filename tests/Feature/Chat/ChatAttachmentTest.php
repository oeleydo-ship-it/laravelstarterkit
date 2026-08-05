<?php

namespace Tests\Feature\Chat;

use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatVisitor;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('chat.attachments.disk'));
    }

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

    // ─── Agent uploads ───

    public function test_agent_can_attach_a_file_to_a_reply(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $response = $this->actingAs($agent)
            ->post(route('chat.conversations.attachments.store', $conversation), [
                'file' => UploadedFile::fake()->create('invoice.pdf', 40, 'application/pdf'),
                'caption' => 'Your invoice',
            ])
            ->assertCreated();

        $this->assertEquals('Your invoice', $response->json('body'));
        $this->assertEquals('invoice.pdf', $response->json('attachment.name'));

        $attachment = ChatAttachment::withoutGlobalScopes()->firstOrFail();
        Storage::disk($attachment->disk)->assertExists($attachment->path);

        // Stored under the tenant so one workspace's files never mingle with another's.
        $this->assertStringStartsWith("chat/{$tenant->id}/{$conversation->id}/", $attachment->path);
    }

    public function test_the_filename_becomes_the_body_when_no_caption_is_given(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->actingAs($agent)
            ->post(route('chat.conversations.attachments.store', $conversation), [
                'file' => UploadedFile::fake()->image('screenshot.png'),
            ])
            ->assertCreated()
            ->assertJsonPath('body', 'screenshot.png');

        $this->assertEquals('screenshot.png', $conversation->fresh()->last_message_preview);
    }

    public function test_disallowed_file_types_are_rejected(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.attachments.store', $conversation), [
                'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('chat_attachments', 0);
        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_oversized_files_are_rejected(): void
    {
        config(['chat.attachments.max_kb' => 100]);

        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        $this->actingAs($agent)
            ->postJson(route('chat.conversations.attachments.store', $conversation), [
                'file' => UploadedFile::fake()->create('big.pdf', 500, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('file');
    }

    public function test_an_agent_cannot_attach_to_another_tenants_conversation(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $conversation = $this->makeConversation($tenantA);
        $foreignAgent = $this->makeUser($tenantB);

        $this->actingAs($foreignAgent)
            ->post(route('chat.conversations.attachments.store', $conversation), [
                'file' => UploadedFile::fake()->create('note.txt', 5, 'text/plain'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('chat_attachments', 0);
    }

    // ─── Downloads ───

    public function test_agent_can_download_an_attachment_from_their_own_workspace(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor(
            $conversation,
            'here you go',
            UploadedFile::fake()->create('receipt.pdf', 5, 'application/pdf')
        );

        $attachment = ChatAttachment::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($agent)
            ->get(route('chat.attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('receipt.pdf');
    }

    public function test_an_agent_from_another_tenant_cannot_download_an_attachment(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $conversation = $this->makeConversation($tenantA);
        app(MessageService::class)->sendAsVisitor(
            $conversation,
            'private',
            UploadedFile::fake()->create('contract.pdf', 5, 'application/pdf')
        );

        $attachment = ChatAttachment::withoutGlobalScopes()->firstOrFail();
        $foreignAgent = $this->makeUser($tenantB);

        // 404, not 403 — the tenant scope hides the row from route model binding.
        $this->actingAs($foreignAgent)
            ->get(route('chat.attachments.download', $attachment))
            ->assertNotFound();
    }

    // ─── Visitor uploads ───

    public function test_visitor_can_upload_a_file_and_download_it_back(): void
    {
        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        $created = $this->post(route('chat.widget.attachments.store', [$tenant->slug, $conversationId]), [
            'file' => UploadedFile::fake()->image('proof.png'),
            'visitor_token' => $token,
        ])->assertCreated();

        $this->assertEquals('proof.png', $created->json('attachment.name'));
        $this->assertTrue($created->json('attachment.is_image'));

        $this->get($created->json('download_url')."?visitor_token={$token}")
            ->assertOk()
            ->assertDownload('proof.png');
    }

    public function test_visitor_cannot_upload_without_a_valid_token(): void
    {
        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        $this->post(route('chat.widget.attachments.store', [$tenant->slug, $start->json('conversation_id')]), [
            'file' => UploadedFile::fake()->image('proof.png'),
            'visitor_token' => 'not-the-right-token',
        ])->assertForbidden();

        $this->assertDatabaseCount('chat_attachments', 0);
    }

    public function test_visitor_cannot_download_an_attachment_from_another_conversation(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);

        // Someone else's chat, with a file in it.
        $otherConversation = $this->makeConversation($tenant, $agent);
        app(MessageService::class)->sendAsAgent(
            $otherConversation,
            $agent,
            'confidential',
            UploadedFile::fake()->create('someone-elses.pdf', 5, 'application/pdf')
        );
        $foreign = ChatAttachment::withoutGlobalScopes()->firstOrFail();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $token = $start->json('visitor_token');
        $conversationId = $start->json('conversation_id');

        // Guessing the id under their own conversation must not reach the file.
        $this->get(route('chat.widget.attachments.download', [$tenant->slug, $conversationId, $foreign->id])
            ."?visitor_token={$token}")
            ->assertNotFound();
    }

    public function test_visitor_download_is_rejected_without_the_matching_token(): void
    {
        $tenant = $this->makeTenant();

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();
        $conversationId = $start->json('conversation_id');

        $created = $this->post(route('chat.widget.attachments.store', [$tenant->slug, $conversationId]), [
            'file' => UploadedFile::fake()->image('mine.png'),
            'visitor_token' => $start->json('visitor_token'),
        ])->assertCreated();

        $this->get($created->json('download_url').'?visitor_token=wrong')->assertForbidden();
    }

    public function test_deleting_an_attachment_removes_the_stored_file(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsAgent(
            $conversation,
            $agent,
            'temp',
            UploadedFile::fake()->create('temp.txt', 2, 'text/plain')
        );

        $attachment = ChatAttachment::withoutGlobalScopes()->firstOrFail();
        $disk = $attachment->disk;
        $path = $attachment->path;

        $attachment->delete();

        Storage::disk($disk)->assertMissing($path);
    }

    public function test_attachments_render_in_the_agent_thread(): void
    {
        $tenant = $this->makeTenant();
        $agent = $this->makeUser($tenant);
        $conversation = $this->makeConversation($tenant, $agent);

        app(MessageService::class)->sendAsVisitor(
            $conversation,
            'see attached',
            UploadedFile::fake()->create('logs.txt', 3, 'text/plain')
        );

        $this->actingAs($agent)
            ->get(route('chat.conversations.show', $conversation))
            ->assertOk()
            ->assertSee('logs.txt');
    }
}
