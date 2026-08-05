<?php

namespace Tests\Feature\Chat;

use App\Models\Setting;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Services\Chat\BusinessHoursService;
use App\Services\Chat\WidgetSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChatWidgetSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    protected function appearancePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Acme Support',
            'greeting' => 'Ask us anything.',
            'launcher_text' => 'Need help?',
            'color' => '#ff5722',
            'offline_message' => 'Back at 9am.',
        ], $overrides);
    }

    protected function hoursPayload(array $overrides = []): array
    {
        $days = [];

        foreach (array_keys(BusinessHoursService::DAYS) as $day) {
            $days[$day] = ['enabled' => '1', 'start' => '09:00', 'end' => '17:00'];
        }

        return array_merge([
            'enabled' => '1',
            'timezone' => 'Europe/London',
            'days' => $days,
        ], $overrides);
    }

    // ─── Appearance ───

    public function test_appearance_falls_back_to_the_workspace_name_and_defaults(): void
    {
        $tenant = $this->makeTenant();

        $appearance = app(WidgetSettingsService::class)->for($tenant);

        $this->assertEquals('Acme', $appearance['title']);
        $this->assertEquals(WidgetSettingsService::defaults()['color'], $appearance['color']);
    }

    public function test_admin_can_customise_the_widget_appearance(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.appearance'), $this->appearancePayload())
            ->assertRedirect();

        $appearance = app(WidgetSettingsService::class)->for($tenant->fresh());

        $this->assertEquals('Acme Support', $appearance['title']);
        $this->assertEquals('#ff5722', $appearance['color']);
        $this->assertEquals('Need help?', $appearance['launcher_text']);
    }

    public function test_customised_appearance_renders_on_the_public_widget(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)->put(route('chat.settings.appearance'), $this->appearancePayload());

        $this->get(route('chat.widget.show', $tenant->slug))
            ->assertOk()
            ->assertSee('Acme Support')
            ->assertSee('Need help?')
            ->assertSee('Ask us anything.')
            ->assertSee('#ff5722', false);
    }

    public function test_a_non_hex_colour_is_rejected(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.appearance'), $this->appearancePayload([
                'color' => 'red; } body { display:none;',
            ]))
            ->assertSessionHasErrors('color');

        $this->assertDatabaseMissing('settings', ['key' => WidgetSettingsService::SETTING_KEY]);
    }

    public function test_members_cannot_change_widget_appearance(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->put(route('chat.settings.appearance'), $this->appearancePayload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => WidgetSettingsService::SETTING_KEY]);
    }

    public function test_appearance_is_tenant_isolated(): void
    {
        $tenantA = $this->makeTenant('acme');
        $tenantB = $this->makeTenant('globex');

        $adminA = $this->makeUser($tenantA, 'admin');
        $this->actingAs($adminA)->put(route('chat.settings.appearance'), $this->appearancePayload());

        $this->get(route('chat.widget.show', $tenantB->slug))
            ->assertOk()
            ->assertDontSee('Acme Support')
            ->assertSee('Globex');
    }

    // ─── Business hours ───

    public function test_hours_are_open_when_the_schedule_is_not_enforced(): void
    {
        $tenant = $this->makeTenant();

        $this->assertTrue(app(BusinessHoursService::class)->isOpen($tenant));
    }

    public function test_open_and_closed_are_evaluated_in_the_configured_timezone(): void
    {
        $tenant = $this->makeTenant();

        Setting::set(BusinessHoursService::SETTING_KEY, [
            'enabled' => true,
            'timezone' => 'Asia/Dubai', // UTC+4
            'days' => [
                'mon' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ],
        ], $tenant->id);

        $hours = app(BusinessHoursService::class);

        // 2026-08-03 is a Monday. 06:00 UTC is 10:00 in Dubai — open.
        $this->assertTrue($hours->isOpen($tenant, Carbon::parse('2026-08-03 06:00:00', 'UTC')));

        // 16:00 UTC is 20:00 in Dubai — closed.
        $this->assertFalse($hours->isOpen($tenant, Carbon::parse('2026-08-03 16:00:00', 'UTC')));
    }

    public function test_a_disabled_day_is_always_closed(): void
    {
        $tenant = $this->makeTenant();

        Setting::set(BusinessHoursService::SETTING_KEY, [
            'enabled' => true,
            'timezone' => 'UTC',
            'days' => ['sun' => ['enabled' => false, 'start' => '00:00', 'end' => '23:59']],
        ], $tenant->id);

        // 2026-08-02 is a Sunday.
        $this->assertFalse(
            app(BusinessHoursService::class)->isOpen($tenant, Carbon::parse('2026-08-02 12:00:00', 'UTC'))
        );
    }

    public function test_an_overnight_shift_wraps_past_midnight(): void
    {
        $tenant = $this->makeTenant();

        Setting::set(BusinessHoursService::SETTING_KEY, [
            'enabled' => true,
            'timezone' => 'UTC',
            'days' => [
                'mon' => ['enabled' => true, 'start' => '22:00', 'end' => '06:00'],
            ],
        ], $tenant->id);

        $hours = app(BusinessHoursService::class);

        $this->assertTrue($hours->isOpen($tenant, Carbon::parse('2026-08-03 23:30:00', 'UTC')));
        $this->assertTrue($hours->isOpen($tenant, Carbon::parse('2026-08-03 02:00:00', 'UTC')));
        $this->assertFalse($hours->isOpen($tenant, Carbon::parse('2026-08-03 12:00:00', 'UTC')));
    }

    public function test_admin_can_save_business_hours(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.hours'), $this->hoursPayload([
                'days' => array_merge($this->hoursPayload()['days'], [
                    'sat' => ['start' => '10:00', 'end' => '14:00'], // no 'enabled' key = unchecked
                ]),
            ]))
            ->assertRedirect();

        $hours = app(BusinessHoursService::class)->for($tenant->fresh());

        $this->assertTrue($hours['enabled']);
        $this->assertEquals('Europe/London', $hours['timezone']);
        $this->assertTrue($hours['days']['mon']['enabled']);
        $this->assertFalse($hours['days']['sat']['enabled']);
    }

    public function test_business_hours_reject_an_unknown_timezone(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->put(route('chat.settings.hours'), $this->hoursPayload(['timezone' => 'Mars/Olympus_Mons']))
            ->assertSessionHasErrors('timezone');
    }

    public function test_members_cannot_change_business_hours(): void
    {
        $tenant = $this->makeTenant();
        $member = $this->makeUser($tenant, 'member');

        $this->actingAs($member)
            ->put(route('chat.settings.hours'), $this->hoursPayload())
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => BusinessHoursService::SETTING_KEY]);
    }

    public function test_widget_start_reports_the_offline_state(): void
    {
        $tenant = $this->makeTenant();

        Setting::set(BusinessHoursService::SETTING_KEY, [
            'enabled' => true,
            'timezone' => 'UTC',
            'days' => [
                'mon' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ],
        ], $tenant->id);

        Carbon::setTestNow(Carbon::parse('2026-08-03 21:00:00', 'UTC'));

        $this->postJson(route('chat.widget.start', $tenant->slug), [])
            ->assertOk()
            ->assertJson(['is_online' => false]);

        Carbon::setTestNow(Carbon::parse('2026-08-03 10:00:00', 'UTC'));

        $this->postJson(route('chat.widget.start', $tenant->slug), [])
            ->assertOk()
            ->assertJson(['is_online' => true]);

        Carbon::setTestNow();
    }

    public function test_visitors_can_still_send_messages_while_offline(): void
    {
        $tenant = $this->makeTenant();

        Setting::set(BusinessHoursService::SETTING_KEY, [
            'enabled' => true,
            'timezone' => 'UTC',
            'days' => ['mon' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00']],
        ], $tenant->id);

        Carbon::setTestNow(Carbon::parse('2026-08-03 23:00:00', 'UTC'));

        $start = $this->postJson(route('chat.widget.start', $tenant->slug), [])->assertOk();

        $this->postJson(
            route('chat.widget.messages.store', [$tenant->slug, $start->json('conversation_id')]),
            ['body' => 'Anyone there?', 'visitor_token' => $start->json('visitor_token')]
        )->assertCreated();

        $this->assertDatabaseHas('chat_messages', ['body' => 'Anyone there?']);

        Carbon::setTestNow();
    }

    public function test_settings_page_shows_appearance_and_hours(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeUser($tenant, 'admin');

        $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->assertSee('Widget Appearance')
            ->assertSee('Business Hours')
            ->assertSee('Conversation Routing');
    }
}
