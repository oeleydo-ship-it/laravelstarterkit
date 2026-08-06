<?php

namespace Tests\Feature\Reviews;

use App\Models\Review;
use App\Models\ReviewSite;
use App\Models\ReviewWidget;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\ModuleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleCatalog::sync();
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Review Co', 'slug' => 'review-co']);
        TenantModule::create(['tenant_id' => $tenant->id, 'module_key' => 'reviews', 'enabled' => true]);

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
        $tenant = Tenant::create(['name' => 'Off', 'slug' => 'off-reviews']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('reviews.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_dashboard(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('reviews.dashboard'))->assertOk()->assertSee('Reviews');
    }

    public function test_public_submit_and_config(): void
    {
        $tenant = $this->makeTenant();
        $site = ReviewSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'reviewkeyabcdefghijklmnopqrstuvw1',
            'name' => 'Site',
            'settings' => ['brand_color' => '#112233'],
        ]);

        ReviewWidget::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'review_site_id' => $site->id,
            'name' => 'Homepage',
            'layout' => 'stacked',
            'min_rating' => 1,
            'max_items' => 6,
            'status' => 'live',
            'style' => [
                'accent_color' => '#f59e0b',
                'max_displays' => 3,
                'frequency_hours' => 12,
            ],
        ]);

        $this->get('/r/'.$site->public_key.'.js')->assertOk();
        $this->get('/r/'.$site->public_key.'/write')->assertOk();

        $this->getJson('/r/'.$site->public_key.'/c')
            ->assertOk()
            ->assertJsonPath('w.0.g.max_displays', 3)
            ->assertJsonPath('w.0.g.frequency_hours', 12);

        $this->postJson('/r/'.$site->public_key.'/s', [
            'rating' => 5,
            'body' => 'Great product',
            'author_name' => 'Ada',
            'email' => 'ada@example.com',
            'website' => '',
        ])->assertCreated();

        $this->assertDatabaseHas('reviews', [
            'review_site_id' => $site->id,
            'author_name' => 'Ada',
            'status' => Review::STATUS_PENDING ?? 'pending',
        ]);
    }

    public function test_public_assets_avoid_vendor_tells(): void
    {
        $js = file_get_contents(resource_path('js/r/loader.js'));
        $css = file_get_contents(resource_path('sass/r/public.scss'));
        foreach (['uplary', 'powered by', 'recurringpress'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $js);
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
        $this->assertStringContainsString('.r-', $css);
        $this->assertStringContainsString('r-close', $js);
        $this->assertStringContainsString('max_displays', $js);
    }
}
