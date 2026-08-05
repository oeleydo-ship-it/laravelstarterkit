<?php

namespace Tests\Feature\Engage;

use App\Models\EngageCampaign;
use App\Models\EngageSite;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Engage\PublicAssetService;
use App\Support\ModuleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngageModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleCatalog::sync();
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Acme', 'slug' => 'acme-engage']);

        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'engage',
            'enabled' => true,
        ]);

        return $tenant;
    }

    protected function makeUser(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    protected function makeSite(Tenant $tenant, string $key = 'abcdefghijklmnopqrstuvwxyz012345'): EngageSite
    {
        return EngageSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => $key,
            'name' => 'Website',
            'allowed_origins' => [],
            'settings' => ['brand_color' => '#112233'],
        ]);
    }

    public function test_dashboard_requires_module(): void
    {
        $tenant = Tenant::create(['name' => 'Off', 'slug' => 'off-engage']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('engage.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_dashboard_when_enabled(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('engage.dashboard'))
            ->assertOk()
            ->assertSee('Engage');
    }

    public function test_public_loader_requires_valid_key(): void
    {
        $this->get('/x/notarealkey00000000000000000000.js')->assertNotFound();
    }

    public function test_public_loader_and_config_work(): void
    {
        $tenant = $this->makeTenant();
        $site = $this->makeSite($tenant);

        EngageCampaign::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'engage_site_id' => $site->id,
            'name' => 'Hello bar',
            'type' => EngageCampaign::TYPE_BAR,
            'status' => EngageCampaign::STATUS_LIVE,
            'priority' => 1,
            'content' => ['headline' => 'Sale today', 'body' => 'Save 20%', 'position' => 'top'],
            'targeting' => ['frequency_hours' => 0, 'delay_ms' => 0, 'device' => 'any'],
            'style' => ['brand_color' => '#112233', 'text_color' => '#ffffff'],
        ]);

        EngageCampaign::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'engage_site_id' => $site->id,
            'name' => 'Draft only',
            'type' => EngageCampaign::TYPE_POPUP,
            'status' => EngageCampaign::STATUS_DRAFT,
            'content' => ['headline' => 'Hidden'],
            'targeting' => [],
            'style' => [],
        ]);

        $this->get('/x/'.$site->public_key.'.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertHeaderMissing('X-Frame-Options');

        $this->getJson('/x/'.$site->public_key.'/c')
            ->assertOk()
            ->assertJsonPath('c', '#112233')
            ->assertJsonCount(1, 'i')
            ->assertJsonPath('i.0.t', 'bar')
            ->assertJsonMissing(['slug' => 'acme-engage']);
    }

    public function test_lead_capture_and_event(): void
    {
        $tenant = $this->makeTenant();
        $site = $this->makeSite($tenant);
        $campaign = EngageCampaign::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'engage_site_id' => $site->id,
            'name' => 'Form',
            'type' => EngageCampaign::TYPE_FORM,
            'status' => EngageCampaign::STATUS_LIVE,
            'content' => ['fields' => ['email' => true]],
            'targeting' => [],
            'style' => [],
        ]);

        $this->postJson('/x/'.$site->public_key.'/e', [
            'i' => $campaign->id,
            't' => 'impression',
            'p' => '/',
        ])->assertNoContent();

        $this->postJson('/x/'.$site->public_key.'/l', [
            'i' => $campaign->id,
            'email' => 'lead@example.com',
            'name' => 'Ada',
            'page_url' => 'https://example.com/pricing',
            'website' => '',
        ])->assertCreated();

        $this->assertDatabaseHas('engage_leads', [
            'tenant_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'email' => 'lead@example.com',
            'name' => 'Ada',
        ]);

        $this->assertDatabaseHas('engage_events', [
            'campaign_id' => $campaign->id,
            'type' => 'impression',
        ]);
    }

    public function test_public_assets_avoid_vendor_tells(): void
    {
        $jsPath = resource_path('js/x/loader.js');
        $cssPath = resource_path('sass/x/public.scss');
        $js = file_get_contents($jsPath);
        $css = file_get_contents($cssPath);

        foreach (['uplary', 'powered by', 'chat-widget', 'recurringpress'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $js);
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }

        $this->assertStringContainsString('.x-root', $css);
        $this->assertDoesNotMatchRegularExpression('/\.engage[\s\{.\-]/', $css);
        $this->assertStringNotContainsString('engage', $js);
        $this->assertStringNotContainsString('engage', $css);
    }

    public function test_asset_service_resolves_opaque_build_names_when_present(): void
    {
        $service = app(PublicAssetService::class);
        $paths = $service->paths();

        $this->assertIsArray($paths);
        $this->assertArrayHasKey('js', $paths);
        $this->assertArrayHasKey('css', $paths);
    }
}
