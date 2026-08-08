<?php

namespace Tests\Feature\Autoblog;

use App\Models\AutoblogDestination;
use App\Models\AutoblogPost;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Jobs\GenerateAutoblogPost;
use App\Jobs\PublishAutoblogPost;
use App\Services\Autoblog\ContentGenerator;
use App\Services\Autoblog\Publisher;
use App\Services\Autoblog\ProviderError;
use App\Services\Chat\Ai\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutoblogModuleTest extends TestCase
{
    use RefreshDatabase;

    private function owner(bool $enabled = true): User
    {
        $tenant = Tenant::factory()->create();
        TenantModule::create(['tenant_id'=>$tenant->id,'module_key'=>'autoblog','enabled'=>$enabled]);
        return User::factory()->create(['tenant_id'=>$tenant->id,'role'=>'owner','status'=>'active']);
    }

    public function test_dashboard_requires_enabled_module(): void
    {
        $this->actingAs($this->owner(false))->get(route('autoblog.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_autoblog_dashboard(): void
    {
        $this->actingAs($this->owner())->get(route('autoblog.dashboard'))->assertOk()->assertSee('AI Autoblog')->assertSee('Kimi K3');
    }

    public function test_generated_post_has_a_separate_preview_and_editor_page(): void
    {
        $user=$this->owner();
        $post=AutoblogPost::create(['tenant_id'=>$user->tenant_id,'created_by'=>$user->id,'topic'=>'Separate post','title'=>'Generated article','slug'=>'generated-article','excerpt'=>'A short summary.','content'=>'<h2>Preview heading</h2><p>Preview body.</p>','status'=>'draft','provider'=>'kimi']);

        $this->actingAs($user)->get(route('autoblog.posts.show',$post))
            ->assertOk()->assertSee('Article preview')->assertSee('Edit generated post')->assertSee('Preview heading');

        $this->get(route('autoblog.dashboard'))
            ->assertOk()->assertSee('Open generated post')->assertDontSee('Preview body.');
    }

    public function test_new_destination_is_selected_in_content_form(): void
    {
        Http::fake(['blog.example.com/*'=>Http::response(['id'=>1,'name'=>'Editor'],200)]);
        $user=$this->owner();

        $response=$this->actingAs($user)->post(route('autoblog.destinations.store'),[
            'name'=>'Company blog',
            'type'=>'wordpress',
            'base_url'=>'https://blog.example.com',
            'username'=>'editor',
            'secret'=>'application-password',
            'is_active'=>'1',
        ]);

        $destination=AutoblogDestination::firstOrFail();
        $response->assertRedirect(route('autoblog.dashboard'))
            ->assertSessionHasInput('destination_id',$destination->id)
            ->assertSessionHas('success','Destination connected, verified, and selected.');

        $this->followRedirects($response)->assertOk()
            ->assertSee('Company blog')
            ->assertSee('value="'.$destination->id.'" selected',false);
        $this->assertNotNull($destination->fresh()->verified_at);
    }

    public function test_legacy_unverified_destination_page_does_not_require_a_timestamp(): void
    {
        $user=$this->owner();
        AutoblogDestination::create(['tenant_id'=>$user->tenant_id,'name'=>'Legacy site','type'=>'wordpress','base_url'=>'https://legacy.example.com','username'=>'editor','secret'=>'password','is_active'=>true]);

        $this->actingAs($user)->get(route('autoblog.destinations.index'))
            ->assertOk()->assertSee('Legacy site')->assertSee('This existing destination has not been checked yet.');
    }

    public function test_posts_library_filters_generated_scheduled_and_published_posts(): void
    {
        $user=$this->owner();
        foreach(['draft','scheduled','published'] as $status)AutoblogPost::create(['tenant_id'=>$user->tenant_id,'created_by'=>$user->id,'topic'=>$status.' article','title'=>ucfirst($status).' article','status'=>$status]);
        $this->actingAs($user)->get(route('autoblog.posts.index'))->assertOk()->assertSee('Draft article')->assertSee('Scheduled article')->assertSee('Published article');
        $this->get(route('autoblog.posts.index',['status'=>'published']))->assertOk()->assertSee('Published article')->assertDontSee('Draft article');
    }

    public function test_generator_parses_and_sanitizes_ai_json(): void
    {
        $ai = new class implements AiProvider {
            public function isConfigured(): bool { return true; }
            public function complete(string $system,array $messages): string { return '```json{"title":"Useful Guide","slug":"useful-guide","excerpt":"Summary","content":"<h2>Hello</h2><script>alert(1)</script><p>World</p>"}```'; }
        };
        $article=(new ContentGenerator($ai))->generate('Topic','Professional','keyword');
        $this->assertSame('Useful Guide',$article['title']);
        $this->assertStringNotContainsString('<script>',$article['content']);
    }

    public function test_wordpress_publisher_uses_rest_api(): void
    {
        Http::fake(['example.com/*'=>Http::response(['id'=>42,'link'=>'https://example.com/useful-guide'],201)]);
        $user=$this->owner(); $tenant=$user->tenant;
        $destination=AutoblogDestination::create(['tenant_id'=>$tenant->id,'name'=>'Site','type'=>'wordpress','base_url'=>'https://example.com','username'=>'editor','secret'=>'application-password']);
        $post=AutoblogPost::create(['tenant_id'=>$tenant->id,'created_by'=>$user->id,'destination_id'=>$destination->id,'topic'=>'Topic','title'=>'Useful Guide','slug'=>'useful-guide','content'=>'<p>Body</p>']);
        $result=(new Publisher)->publish($post,$destination);
        $this->assertSame('42',$result['id']);
        Http::assertSent(fn($r)=>$r->url()==='https://example.com/wp-json/wp/v2/posts' && $r['status']==='publish');
    }

    public function test_direct_destination_url_uses_generic_rest_payload(): void
    {
        Http::fake(['publisher.example/*'=>Http::response(['id'=>99,'url'=>'https://publisher.example/posts/99'],201)]);
        $user=$this->owner();
        $post=AutoblogPost::create(['tenant_id'=>$user->tenant_id,'created_by'=>$user->id,'destination_url'=>'https://publisher.example/api/posts','topic'=>'Topic','title'=>'Direct post','slug'=>'direct-post','content'=>'<p>Body</p>']);

        $result=(new Publisher)->publishToUrl($post,$post->destination_url);

        $this->assertSame('99',$result['id']);
        Http::assertSent(fn($request)=>$request->url()==='https://publisher.example/api/posts' && $request['source']==='autoblog' && $request['title']==='Direct post');
    }

    public function test_raw_connection_errors_are_replaced_with_actionable_copy(): void
    {
        $message=ProviderError::friendly("cURL error 7: Failed to connect to api.moonshot.ai port 443");
        $this->assertStringContainsString('could not be reached',$message);
        $this->assertStringNotContainsString('cURL',$message);
    }

    public function test_wrapped_provider_connection_errors_are_actionable(): void
    {
        $message=ProviderError::friendly('The AI provider could not be reached.');
        $this->assertStringContainsString('internet/firewall',$message);
        $this->assertStringNotContainsString('Article generation failed',$message);
    }

    public function test_generation_is_queued_instead_of_running_in_the_web_request(): void
    {
        Queue::fake(); $user=$this->owner();
        $this->actingAs($user)->post(route('autoblog.posts.store'),['topic'=>'Queued topic','tone'=>'Professional','keywords'=>'queue, ai'])
            ->assertRedirect()->assertSessionHas('success');
        $post=AutoblogPost::firstOrFail();
        $this->assertSame('queued',$post->status);
        Queue::assertPushed(GenerateAutoblogPost::class,fn($job)=>$job->postId===$post->id);
    }

    public function test_custom_destination_url_is_optional_and_is_saved_when_supplied(): void
    {
        Queue::fake(); $user=$this->owner();
        $this->actingAs($user)->post(route('autoblog.posts.store'),['topic'=>'Direct URL','tone'=>'Professional','destination_url'=>'https://publisher.example/api/posts'])
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('autoblog_posts',['tenant_id'=>$user->tenant_id,'destination_id'=>null,'destination_url'=>'https://publisher.example/api/posts']);
        Queue::assertPushed(GenerateAutoblogPost::class);
    }

    public function test_cron_command_queues_due_scheduled_posts_once(): void
    {
        Queue::fake(); $user=$this->owner();$tenant=$user->tenant;
        $destination=AutoblogDestination::create(['tenant_id'=>$tenant->id,'name'=>'Site','type'=>'webhook','base_url'=>'https://example.com/hook','secret'=>'token']);
        $post=AutoblogPost::create(['tenant_id'=>$tenant->id,'created_by'=>$user->id,'destination_id'=>$destination->id,'topic'=>'Scheduled','title'=>'Scheduled','content'=>'<p>Body</p>','status'=>'scheduled','scheduled_at'=>now()->subMinute()]);
        $this->artisan('autoblog:dispatch-scheduled')->assertSuccessful();
        $this->assertSame('publish_queued',$post->fresh()->status);
        Queue::assertPushed(PublishAutoblogPost::class,1);
    }
}
