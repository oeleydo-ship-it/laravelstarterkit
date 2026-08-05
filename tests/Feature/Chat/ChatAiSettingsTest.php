<?php

namespace Tests\Feature\Chat;

use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\Ai\NullAiProvider;
use App\Services\Chat\Ai\OpenAiCompatibleAiProvider;
use App\Services\Chat\AiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatAiSettingsTest extends TestCase
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

    public function test_knowledge_settings_page_shows_openai_and_kimi_fields(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->actingAs($admin)
            ->get(route('chat.settings.index', ['tab' => 'knowledge']))
            ->assertOk()
            ->assertSee('AI provider')
            ->assertSee('OpenAI')
            ->assertSee('Kimi K3')
            ->assertSee('name="openai_key"', false)
            ->assertSee('name="kimi_key"', false);
    }

    public function test_admin_can_save_openai_provider_settings(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->put(route('chat.settings.knowledge'), [
                'provider' => 'openai',
                'openai_key' => 'sk-test-openai-key-1234',
                'openai_model' => 'gpt-4o-mini',
                'openai_base_url' => 'https://api.openai.com/v1',
                'kimi_model' => 'kimi-k3',
                'kimi_base_url' => 'https://api.moonshot.ai/v1',
                'auto_reply' => '1',
            ])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'knowledge']));

        $settings = app(AiSettingsService::class)->for($tenant);

        $this->assertEquals('openai', $settings['provider']);
        $this->assertEquals('sk-test-openai-key-1234', $settings['openai']['key']);
        $this->assertTrue(app(AiSettingsService::class)->makeProvider($tenant)->isConfigured());
    }

    public function test_admin_can_save_kimi_provider_settings(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->put(route('chat.settings.knowledge'), [
                'provider' => 'kimi',
                'kimi_key' => 'ms-kimi-secret-9999',
                'kimi_model' => 'kimi-k3',
                'kimi_base_url' => 'https://api.moonshot.ai/v1',
                'openai_model' => 'gpt-4o-mini',
                'openai_base_url' => 'https://api.openai.com/v1',
            ])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'knowledge']));

        $provider = app(AiSettingsService::class)->makeProvider($tenant);

        $this->assertInstanceOf(OpenAiCompatibleAiProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_blank_api_key_keeps_the_existing_one(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $ai = app(AiSettingsService::class);

        $ai->save($tenant, [
            'provider' => 'openai',
            'openai_key' => 'sk-keep-me-abcd',
            'openai_model' => 'gpt-4o-mini',
            'openai_base_url' => 'https://api.openai.com/v1',
        ]);

        $this->actingAs($admin)
            ->put(route('chat.settings.knowledge'), [
                'provider' => 'openai',
                'openai_key' => '',
                'openai_model' => 'gpt-4o',
                'openai_base_url' => 'https://api.openai.com/v1',
                'kimi_model' => 'kimi-k3',
                'kimi_base_url' => 'https://api.moonshot.ai/v1',
            ])
            ->assertRedirect();

        $settings = $ai->for($tenant);
        $this->assertEquals('sk-keep-me-abcd', $settings['openai']['key']);
        $this->assertEquals('gpt-4o', $settings['openai']['model']);
    }

    public function test_openai_compatible_provider_posts_chat_completions(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => '  Hello from OpenAI  ']],
                ],
            ], 200),
        ]);

        $provider = new OpenAiCompatibleAiProvider([
            'key' => 'sk-test',
            'model' => 'gpt-4o-mini',
            'base_url' => 'https://api.openai.com/v1',
            'max_tokens' => 200,
        ]);

        $reply = $provider->complete('Be helpful.', [
            ['role' => 'user', 'content' => 'Hi'],
        ]);

        $this->assertEquals('Hello from OpenAI', $reply);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request['model'] === 'gpt-4o-mini'
                && $request['messages'][0]['role'] === 'system';
        });
    }

    public function test_turning_provider_off_returns_null_provider(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->put(route('chat.settings.knowledge'), [
                'provider' => 'null',
                'openai_model' => 'gpt-4o-mini',
                'openai_base_url' => 'https://api.openai.com/v1',
                'kimi_model' => 'kimi-k3',
                'kimi_base_url' => 'https://api.moonshot.ai/v1',
            ])
            ->assertRedirect();

        $this->assertInstanceOf(
            NullAiProvider::class,
            app(AiSettingsService::class)->makeProvider($tenant)
        );
    }
}
