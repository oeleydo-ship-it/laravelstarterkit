<?php

namespace Tests\Feature\Chat;

use App\Http\Controllers\Chat\ChatSettingsController;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSettingsTabsTest extends TestCase
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

    protected function makeAdmin(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
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
            'pre_chat_message' => 'Who are you?',
        ], $overrides);
    }

    // ─── Tab layout ───

    public function test_every_tab_is_rendered_with_its_pane(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $response = $this->actingAs($admin)->get(route('chat.settings.index'))->assertOk();

        foreach (ChatSettingsController::TABS as $key => $label) {
            $response->assertSee('pane-'.$key, false);
            // Escaped: one label contains an ampersand.
            $response->assertSee($label);
        }

        // Content from each pane, so a partial that fails to include is caught.
        $response->assertSee('Conversation Routing')
            ->assertSee('Widget Appearance')
            ->assertSee('Business Hours')
            ->assertSee('Knowledge Base')
            ->assertSee('Notifications &amp; Webhooks', false)
            ->assertSee('API Tokens')
            ->assertSee('Install the widget');
    }

    public function test_the_first_tab_is_active_by_default(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->assertSee('id="pane-routing" role="tabpanel"', false)
            ->assertSeeInOrder(['tab-pane fade show active', 'id="pane-routing"'], false);
    }

    public function test_a_requested_tab_is_the_one_opened(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->actingAs($admin)
            ->get(route('chat.settings.index', ['tab' => 'hours']))
            ->assertOk()
            ->assertSeeInOrder(['tab-pane fade show active', 'id="pane-hours"'], false);
    }

    public function test_an_unknown_tab_falls_back_to_the_first_one(): void
    {
        $admin = $this->makeAdmin($this->makeTenant());

        $this->actingAs($admin)
            ->get(route('chat.settings.index', ['tab' => 'nonsense']))
            ->assertOk()
            ->assertSeeInOrder(['tab-pane fade show active', 'id="pane-routing"'], false);
    }

    // ─── Saves return to their own tab ───

    public function test_each_save_redirects_back_to_the_tab_it_came_from(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin);

        $this->put(route('chat.settings.update'), ['routing_strategy' => 'round_robin'])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'routing']));

        $this->put(route('chat.settings.appearance'), $this->appearancePayload())
            ->assertRedirect(route('chat.settings.index', ['tab' => 'appearance']));

        $this->put(route('chat.settings.integrations'), ['mail_enabled' => '1'])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'notifications']));

        $this->post(route('chat.settings.tokens.store'), ['name' => 'CRM sync'])
            ->assertRedirect(route('chat.settings.index', ['tab' => 'tokens']));
    }

    public function test_a_failed_save_reopens_the_tab_holding_the_error(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        // A validation failure redirects back to the settings page with the
        // errors in the session; the pane owning the first error must open, or
        // the message renders on a tab nobody is looking at.
        $this->actingAs($admin)
            ->from(route('chat.settings.index'))
            ->put(route('chat.settings.integrations'), ['webhook_url' => 'http://insecure.test/hook'])
            ->assertSessionHasErrors('webhook_url');

        $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->assertSeeInOrder(['tab-pane fade show active', 'id="pane-notifications"'], false);
    }

    public function test_an_explicit_tab_wins_over_the_error_tab(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->from(route('chat.settings.index'))
            ->put(route('chat.settings.appearance'), $this->appearancePayload(['color' => 'nope']))
            ->assertSessionHasErrors('color');

        $this->actingAs($admin)
            ->get(route('chat.settings.index', ['tab' => 'tokens']))
            ->assertOk()
            ->assertSeeInOrder(['tab-pane fade show active', 'id="pane-tokens"'], false);
    }

    public function test_the_routing_pane_still_shows_the_selected_strategy(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)->put(route('chat.settings.update'), ['routing_strategy' => 'least_busy']);

        // Regression: the tab variable must not shadow the controller's
        // `$current`, which is the selected routing strategy.
        $html = $this->actingAs($admin)
            ->get(route('chat.settings.index'))
            ->assertOk()
            ->getContent();

        // Matched with a regex because the attribute spans lines in the markup.
        $this->assertMatchesRegularExpression(
            '/id="strategy-least_busy"[^>]*\bchecked\b/s',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/id="strategy-manual"[^>]*\bchecked\b/s',
            $html,
        );
    }

    // ─── Install pane ───

    public function test_the_install_pane_shows_the_tenants_own_embed_snippet(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $this->actingAs($admin)
            ->get(route('chat.settings.index', ['tab' => 'install']))
            ->assertOk()
            ->assertSee(route('chat.widget.embed', $tenant->slug), false)
            ->assertSee(route('chat.widget.show', $tenant->slug), false);
    }

    public function test_members_still_cannot_reach_settings_at_all(): void
    {
        $tenant = $this->makeTenant();
        $member = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->actingAs($member)
            ->get(route('chat.settings.index', ['tab' => 'tokens']))
            ->assertRedirect(route('dashboard'));
    }
}
