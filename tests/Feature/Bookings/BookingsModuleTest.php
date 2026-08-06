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
            'website' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_appointments', [
            'guest_email' => 'ada@example.com',
            'booking_service_id' => $service->id,
        ]);
    }
}
