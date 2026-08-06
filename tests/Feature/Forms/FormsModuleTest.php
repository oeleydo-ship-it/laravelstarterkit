<?php

namespace Tests\Feature\Forms;

use App\Models\Form;
use App\Models\FormSite;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\ModuleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleCatalog::sync();
    }

    protected function makeTenant(): Tenant
    {
        $tenant = Tenant::create(['name' => 'Form Co', 'slug' => 'form-co']);
        TenantModule::create(['tenant_id' => $tenant->id, 'module_key' => 'forms', 'enabled' => true]);

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
        $tenant = Tenant::create(['name' => 'Off', 'slug' => 'off-forms']);
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('forms.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_owner_can_open_dashboard(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)->get(route('forms.dashboard'))->assertOk()->assertSee('Forms');
    }

    public function test_owner_can_list_and_create_from_template(): void
    {
        $tenant = $this->makeTenant();
        $user = $this->makeUser($tenant);

        $this->actingAs($user)
            ->get(route('forms.forms.index'))
            ->assertOk()
            ->assertSee('Forms');

        $this->actingAs($user)
            ->get(route('forms.forms.create'))
            ->assertOk()
            ->assertSee('Choose a template');

        $this->actingAs($user)
            ->get(route('forms.forms.create', ['template' => 'contact_lead']))
            ->assertOk()
            ->assertSee('Contact us')
            ->assertSee('How many times');

        $this->actingAs($user)
            ->post(route('forms.forms.store'), [
                'name' => 'Contact us',
                'type' => 'lead',
                'status' => 'draft',
                'thank_you' => 'Thanks!',
                'display_mode' => 'popup',
                'delay_ms' => 500,
                'frequency_hours' => 12,
                'max_displays' => 2,
                'fields' => [
                    ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => '1'],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => '1'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('forms', [
            'tenant_id' => $tenant->id,
            'name' => 'Contact us',
        ]);

        $form = Form::where('tenant_id', $tenant->id)->where('name', 'Contact us')->first();
        $this->assertSame('popup', $form->settings['display_mode'] ?? null);
        $this->assertSame(2, $form->settings['max_displays'] ?? null);
    }

    public function test_public_submit(): void
    {
        $tenant = $this->makeTenant();
        $site = FormSite::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'public_key' => 'formkeyabcdefghijklmnopqrstuvwxy1',
            'name' => 'Site',
            'settings' => ['brand_color' => '#112233'],
        ]);
        $form = Form::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'form_site_id' => $site->id,
            'name' => 'Contact',
            'type' => 'lead',
            'status' => 'live',
            'fields' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ],
            'settings' => [
                'display_mode' => 'inline',
                'max_displays' => 5,
                'frequency_hours' => 24,
            ],
            'thank_you' => 'Thanks!',
        ]);

        $this->get('/f/'.$site->public_key.'.js')->assertOk();

        $this->getJson('/f/'.$site->public_key.'/c')
            ->assertOk()
            ->assertJsonPath('i.0.s.max_displays', 5);

        $this->postJson('/f/'.$site->public_key.'/s', [
            'i' => $form->id,
            'answers' => ['name' => 'Ada', 'email' => 'ada@example.com'],
            'page_url' => 'https://example.com',
            'website' => '',
        ])->assertCreated();

        $this->assertDatabaseHas('form_submissions', [
            'form_id' => $form->id,
            'email' => 'ada@example.com',
        ]);
    }

    public function test_public_assets_avoid_vendor_tells(): void
    {
        $js = file_get_contents(resource_path('js/f/loader.js'));
        $css = file_get_contents(resource_path('sass/f/public.scss'));
        foreach (['uplary', 'powered by', 'recurringpress'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $js);
            $this->assertStringNotContainsStringIgnoringCase($needle, $css);
        }
        $this->assertStringContainsString('.f-root', $css);
        $this->assertStringContainsString('max_displays', $js);
        $this->assertStringContainsString('f-close', $js);
    }
}
