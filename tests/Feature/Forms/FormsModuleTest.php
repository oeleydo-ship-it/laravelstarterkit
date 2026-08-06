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
            'thank_you' => 'Thanks!',
        ]);

        $this->get('/f/'.$site->public_key.'.js')->assertOk();

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
    }
}
