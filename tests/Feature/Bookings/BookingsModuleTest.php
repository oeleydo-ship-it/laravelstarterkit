<?php

namespace Tests\Feature\Bookings;

use App\Models\BookingAvailability;
use App\Models\BookingService;
use App\Models\BookingSite;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\ModuleCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleCatalog::sync();
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Book Co', 'slug' => 'book-co']);
        TenantModule::create([
            'tenant_id' => $tenant->id,
            'module_key' => 'bookings',
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

    public function test_dashboard_requires_module(): void
    {
        $tenant = Tenant::create(['name' => 'Off', 'slug' => 'off-book']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('bookings.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_dashboard(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('bookings.dashboard'))
            ->assertOk()
            ->assertSee('Bookings');
    }

    public function test_public_booking_flow(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'bookkeyabcdefghijklmnopqrstuvwx12',
            'name' => 'Demos',
            'timezone' => 'UTC',
            'settings' => ['brand_color' => '#0f766e'],
        ]);

        $service = BookingService::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'booking_site_id' => $site->id,
            'name' => 'Demo call',
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'active' => true,
        ]);

        // Next weekday Mon-Fri window
        $day = Carbon::now('UTC')->next(Carbon::TUESDAY)->startOfDay();
        BookingAvailability::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'booking_site_id' => $site->id,
            'weekday' => $day->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $this->get('/b/'.$site->public_key)
            ->assertOk()
            ->assertSee('Demo call');

        $slots = $this->getJson('/b/'.$site->public_key.'/slots?service_id='.$service->id.'&date='.$day->toDateString())
            ->assertOk()
            ->json('slots');

        $this->assertNotEmpty($slots);

        $this->post('/b/'.$site->public_key.'/book', [
            'service_id' => $service->id,
            'starts_at' => $slots[0],
            'guest_name' => 'Ada',
            'guest_email' => 'ada@example.com',
            'guest_phone' => '555-0100',
            'notes' => 'Looking forward to it',
            'b_meta_hp' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_appointments', [
            'guest_email' => 'ada@example.com',
            'guest_name' => 'Ada',
            'guest_phone' => '555-0100',
            'notes' => 'Looking forward to it',
            'booking_service_id' => $service->id,
        ]);
    }

    public function test_honeypot_does_not_create_appointment(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'bookhoneypotkeyabcdefghijklmnopqrst',
            'name' => 'Demos',
            'timezone' => 'UTC',
            'settings' => [],
        ]);
        $service = BookingService::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'booking_site_id' => $site->id,
            'name' => 'Call',
            'duration_minutes' => 30,
            'active' => true,
        ]);
        $day = Carbon::now('UTC')->next(Carbon::WEDNESDAY)->startOfDay();
        BookingAvailability::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'booking_site_id' => $site->id,
            'weekday' => $day->dayOfWeek,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        $slots = $this->getJson('/b/'.$site->public_key.'/slots?service_id='.$service->id.'&date='.$day->toDateString())->json('slots');

        $this->post('/b/'.$site->public_key.'/book', [
            'service_id' => $service->id,
            'starts_at' => $slots[0],
            'guest_name' => 'Bot',
            'guest_email' => 'bot@example.com',
            'b_meta_hp' => 'https://spam.example',
        ])->assertRedirect();

        $this->assertDatabaseMissing('booking_appointments', [
            'guest_email' => 'bot@example.com',
        ]);
    }

    public function test_widget_boot_includes_display_limits(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'bookwidgetkeyabcdefghijklmnopqrstu',
            'name' => 'Demos',
            'timezone' => 'UTC',
            'settings' => [
                'brand_color' => '#0f766e',
                'widget_enabled' => true,
                'widget_label' => 'Schedule demo',
                'max_displays' => 4,
                'frequency_hours' => 6,
            ],
        ]);

        $response = $this->get('/b/'.$site->public_key.'.js')->assertOk();
        $body = $response->getContent();
        $this->assertStringContainsString('window.__B=', $body);
        $this->assertStringContainsString('"max_displays":4', $body);
        $this->assertStringContainsString('"frequency_hours":6', $body);
        $this->assertStringContainsString('Schedule demo', $body);
    }

    public function test_owner_can_save_widget_display_settings(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('bookings.settings'))
            ->assertOk()
            ->assertSee('How many times');

        $this->actingAs($user)
            ->put(route('bookings.settings.update'), [
                'name' => 'Bookings',
                'timezone' => 'UTC',
                'brand_color' => '#0f766e',
                'allowed_origins' => '',
                'widget_enabled' => '1',
                'widget_label' => 'Book now',
                'widget_position' => 'bottom-left',
                'frequency_hours' => 8,
                'max_displays' => 1,
            ])
            ->assertRedirect();

        $site = BookingSite::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
        $this->assertTrue((bool) ($site->settings['widget_enabled'] ?? false));
        $this->assertSame(1, $site->settings['max_displays'] ?? null);
        $this->assertSame('Book now', $site->settings['widget_label'] ?? null);
    }

    public function test_public_assets_avoid_vendor_tells(): void
    {
        $js = file_get_contents(resource_path('js/b/loader.js'));
        $css = file_get_contents(resource_path('sass/b/public.scss'));
        foreach (['uplary', 'powered by', 'recurringpress'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $js);
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
        $this->assertStringContainsString('b-close', $js);
        $this->assertStringContainsString('max_displays', $js);
    }
}
