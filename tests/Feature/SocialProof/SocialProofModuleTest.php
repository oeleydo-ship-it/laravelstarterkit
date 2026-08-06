<?php

namespace Tests\Feature\SocialProof;

use App\Models\SocialProofEvent;
use App\Models\SocialProofSite;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\ModuleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialProofModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleCatalog::sync();
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Proof Co', 'slug' => 'proof-co']);
        TenantModule::create(['tenant_id' => $tenant->id, 'module_key' => 'socialproof', 'enabled' => true]);

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

    public function test_dashboard_requires_module(): void
    {
        $tenant = Tenant::create(['name' => 'Off', 'slug' => 'off-proof']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('socialproof.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_dashboard_and_save_settings(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('socialproof.dashboard'))->assertOk()->assertSee('Social Proof');

        $this->actingAs($user)->put(route('socialproof.settings.update'), [
            'name' => 'Storefront',
            'allowed_origins' => "https://shop.test\n",
            'enabled' => '1',
            'position' => 'bottom-right',
            'initial_delay_ms' => 2000,
            'display_duration_ms' => 4500,
            'interval_ms' => 8000,
            'max_displays' => 3,
            'max_per_page' => 2,
            'include_fake' => '1',
            'include_api' => '1',
            'include_live_subscribers' => '0',
            'include_live_bookings' => '0',
            'accent_color' => '#0f766e',
            'purchase_verb' => 'bought',
            'subscribe_verb' => 'joined',
        ])->assertRedirect();

        $site = SocialProofSite::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($site);
        $this->assertSame(3, $site->resolvedSettings()['max_displays']);
        $this->assertSame('bought', $site->resolvedSettings()['purchase_verb']);
    }

    public function test_public_boot_config_and_ingest(): void
    {
        $tenant = $this->makeTenant();
        $site = SocialProofSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'socialproofkeyabcdefghijklmnopqrst',
            'name' => 'Site',
            'settings' => array_merge(SocialProofSite::defaultSettings(), [
                'max_displays' => 4,
                'include_fake' => true,
                'include_api' => true,
                'include_live_subscribers' => false,
                'include_live_bookings' => false,
            ]),
        ]);

        SocialProofEvent::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'social_proof_site_id' => $site->id,
            'type' => 'purchase',
            'source' => 'fake',
            'customer_name' => 'Maya',
            'location' => 'Berlin',
            'item_name' => 'Starter',
            'is_active' => true,
            'occurred_at' => now(),
        ]);

        $this->get('/sp/'.$site->public_key.'.js')->assertOk();

        $this->getJson('/sp/'.$site->public_key.'/c')
            ->assertOk()
            ->assertJsonPath('g.max_displays', 4)
            ->assertJsonPath('i.0.n', 'Maya');

        $this->postJson('/sp/'.$site->public_key.'/e', [
            'type' => 'subscribe',
            'customer_name' => 'Lee',
            'location' => 'Austin',
            'item_name' => 'Newsletter',
            'website' => '',
        ])->assertCreated();

        $this->assertDatabaseHas('social_proof_events', [
            'social_proof_site_id' => $site->id,
            'customer_name' => 'Lee',
            'source' => SocialProofEvent::SOURCE_API,
            'type' => SocialProofEvent::TYPE_SUBSCRIBE,
        ]);
    }

    public function test_public_assets_avoid_vendor_tells(): void
    {
        $js = file_get_contents(resource_path('js/sp/loader.js'));
        $css = file_get_contents(resource_path('sass/sp/public.scss'));
        foreach (['uplary', 'powered by', 'recurringpress'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $js);
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
        $this->assertStringContainsString('.sp-', $css);
        $this->assertStringContainsString('max_displays', $js);
        $this->assertStringContainsString('localStorage', $js);
    }
}
