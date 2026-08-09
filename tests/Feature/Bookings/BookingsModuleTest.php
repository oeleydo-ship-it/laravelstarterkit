<?php

namespace Tests\Feature\Bookings;

use App\Mail\BookingReminderMail;
use App\Models\BookingAppointment;
use App\Models\BookingAvailability;
use App\Models\BookingService;
use App\Models\BookingSite;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Bookings\BookingPaymentService;
use App\Support\ModuleCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

        $appointment = BookingAppointment::withoutGlobalScopes()->where('guest_email', 'ada@example.com')->firstOrFail();
        $this->assertNotEmpty($appointment->confirmation_code);
        $manageUrl = '/b/'.$site->public_key.'/manage/'.$appointment->confirmation_code;
        $this->get($manageUrl)->assertOk()->assertSee('Manage appointment');
        $this->post($manageUrl.'/reschedule', ['starts_at' => $slots[1]])->assertRedirect();
        $this->assertNotNull($appointment->fresh()->rescheduled_at);
        $this->assertSame(Carbon::parse($slots[1])->utc()->timestamp, $appointment->fresh()->starts_at->timestamp);
        $this->get($manageUrl.'/calendar.ics')->assertOk()->assertHeader('content-type', 'text/calendar; charset=utf-8');
        $this->post($manageUrl.'/cancel', ['reason' => 'Schedule changed'])->assertRedirect();
        $this->assertSame(BookingAppointment::STATUS_CANCELLED, $appointment->fresh()->status);
        $this->assertSame('Schedule changed', $appointment->fresh()->cancellation_reason);
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
                'minimum_notice_hours' => 4,
                'maximum_advance_days' => 60,
            ])
            ->assertRedirect();

        $site = BookingSite::withoutGlobalScopes()->where('tenant_id', $tenant->id)->first();
        $this->assertTrue((bool) ($site->settings['widget_enabled'] ?? false));
        $this->assertSame(1, $site->settings['max_displays'] ?? null);
        $this->assertSame('Book now', $site->settings['widget_label'] ?? null);
        $this->assertSame(4, $site->settings['minimum_notice_hours'] ?? null);
        $this->assertSame(60, $site->settings['maximum_advance_days'] ?? null);
    }

    public function test_owner_can_manage_appointment_assignment_and_internal_notes(): void
    {
        $tenant = $this->makeTenant();
        $owner = $this->makeUser($tenant);
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'managekeyabcdefghijklmnopqrstuvwxy', 'name' => 'Calls', 'timezone' => 'UTC']);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Call', 'duration_minutes' => 30]);
        $appointment = BookingAppointment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'booking_service_id' => $service->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30), 'guest_name' => 'Guest', 'guest_email' => 'guest@example.com', 'status' => 'scheduled']);

        $this->actingAs($owner)->get(route('bookings.appointments.show', $appointment))->assertOk();
        $this->actingAs($owner)->put(route('bookings.appointments.update', $appointment), [
            'assigned_to' => $owner->id,
            'internal_notes' => 'Prepare the demo environment.',
        ])->assertRedirect();

        $this->assertDatabaseHas('booking_appointments', ['id' => $appointment->id, 'assigned_to' => $owner->id, 'internal_notes' => 'Prepare the demo environment.']);
    }

    public function test_due_guest_reminder_is_sent_once(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'reminderkeyabcdefghijklmnopqrstuv', 'name' => 'Calls', 'timezone' => 'UTC', 'settings' => ['reminders_enabled' => true, 'reminder_hours' => 24]]);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Call', 'duration_minutes' => 30]);
        $appointment = BookingAppointment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'booking_service_id' => $service->id, 'starts_at' => now()->addHours(23), 'ends_at' => now()->addHours(23)->addMinutes(30), 'guest_name' => 'Guest', 'guest_email' => 'guest@example.com', 'status' => 'scheduled', 'confirmation_code' => 'remindercode']);

        $this->artisan('bookings:send-reminders')->assertSuccessful();
        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Mail::assertSent(BookingReminderMail::class, 1);
        $this->assertNotNull($appointment->fresh()->reminder_sent_at);
    }

    public function test_calendar_feed_contains_scheduled_bookings(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'calendarpublicabcdefghijklmnopqrst', 'calendar_token' => 'calendarsecretabcdefghijklmnopqrst', 'name' => 'Team Calendar', 'timezone' => 'UTC']);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Strategy Call', 'duration_minutes' => 30, 'location_details' => 'https://meet.example.test/room']);
        BookingAppointment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'booking_service_id' => $service->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30), 'guest_name' => 'Ada', 'guest_email' => 'ada@example.com', 'status' => 'scheduled']);

        $this->get('/calendar/bookings/'.$site->calendar_token.'.ics')->assertOk()
            ->assertSee('BEGIN:VCALENDAR')->assertSee('Strategy Call')->assertSee('meet.example.test');
    }

    public function test_paid_service_holds_slot_and_redirects_to_checkout(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'paidkeyabcdefghijklmnopqrstuvwxy', 'name' => 'Paid Calls', 'timezone' => 'UTC', 'settings' => ['minimum_notice_hours' => 0]]);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Consultation', 'duration_minutes' => 30, 'active' => true, 'requires_payment' => true, 'price_cents' => 7500, 'currency' => 'USD']);
        $day = now('UTC')->next(Carbon::THURSDAY)->startOfDay();
        BookingAvailability::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'weekday' => $day->dayOfWeek, 'start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $slot = $this->getJson('/b/'.$site->public_key.'/slots?service_id='.$service->id.'&date='.$day->toDateString())->json('slots.0');

        $payments = \Mockery::mock(BookingPaymentService::class);
        $payments->shouldReceive('checkout')->once()->andReturn('https://checkout.stripe.test/session');
        $this->app->instance(BookingPaymentService::class, $payments);

        $this->post('/b/'.$site->public_key.'/book', ['service_id' => $service->id, 'starts_at' => $slot, 'guest_name' => 'Buyer', 'guest_email' => 'buyer@example.com', 'b_meta_hp' => ''])
            ->assertRedirect('https://checkout.stripe.test/session');

        $this->assertDatabaseHas('booking_appointments', ['guest_email' => 'buyer@example.com', 'status' => BookingAppointment::STATUS_PENDING_PAYMENT, 'payment_status' => 'pending', 'amount_cents' => 7500]);
    }

    public function test_expired_payment_hold_releases_slot(): void
    {
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'expirekeyabcdefghijklmnopqrstuv', 'name' => 'Calls', 'timezone' => 'UTC']);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Call', 'duration_minutes' => 30]);
        $appointment = BookingAppointment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'booking_service_id' => $service->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30), 'guest_name' => 'Buyer', 'guest_email' => 'buyer@example.com', 'status' => BookingAppointment::STATUS_PENDING_PAYMENT, 'payment_status' => 'pending', 'payment_expires_at' => now()->subMinute()]);

        $this->artisan('bookings:expire-payment-holds')->assertSuccessful();
        $this->assertSame(BookingAppointment::STATUS_CANCELLED, $appointment->fresh()->status);
        $this->assertSame('expired', $appointment->fresh()->payment_status);
    }

    public function test_webhook_can_confirm_retried_checkout_by_appointment_metadata(): void
    {
        Mail::fake();
        $tenant = $this->makeTenant();
        $site = BookingSite::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'public_key' => 'webhookkeyabcdefghijklmnopqrstuv', 'name' => 'Calls', 'timezone' => 'UTC']);
        $service = BookingService::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'name' => 'Paid call', 'duration_minutes' => 30]);
        $appointment = BookingAppointment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'booking_site_id' => $site->id, 'booking_service_id' => $service->id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(30), 'guest_name' => 'Buyer', 'guest_email' => 'buyer@example.com', 'status' => BookingAppointment::STATUS_PENDING_PAYMENT, 'payment_status' => 'pending', 'stripe_checkout_session_id' => 'new_session']);

        app(BookingPaymentService::class)->markPaid('older_paid_session', $appointment->id);

        $this->assertSame(BookingAppointment::STATUS_SCHEDULED, $appointment->fresh()->status);
        $this->assertSame('paid', $appointment->fresh()->payment_status);
        $this->assertSame('older_paid_session', $appointment->fresh()->stripe_checkout_session_id);
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
