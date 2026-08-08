<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_button_fails_gracefully_when_not_configured(): void
    {
        config(['services.google.client_id'=>null,'services.google.client_secret'=>null]);
        $this->from(route('login'))->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))->assertSessionHasErrors('google');
    }

    public function test_google_callback_creates_user_and_starts_onboarding(): void
    {
        $google=(new SocialiteUser)->map(['id'=>'google-123','name'=>'Ada Lovelace','email'=>'ada@example.com','avatar'=>'https://example.com/avatar.jpg']);
        $provider=Mockery::mock(); $provider->shouldReceive('user')->once()->andReturn($google);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))->assertRedirect(route('onboarding.show'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users',['email'=>'ada@example.com','google_id'=>'google-123','role'=>'owner']);
    }

    public function test_google_callback_links_an_existing_email_without_duplication(): void
    {
        $existing=User::factory()->create(['tenant_id'=>Tenant::factory()->create()->id,'email'=>'member@example.com','google_id'=>null,'status'=>'active']);
        $google=(new SocialiteUser)->map(['id'=>'google-456','name'=>'Member','email'=>'member@example.com','avatar'=>null]);
        $provider=Mockery::mock(); $provider->shouldReceive('user')->once()->andReturn($google);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1,User::withoutGlobalScopes()->where('email','member@example.com')->count());
        $this->assertSame('google-456',$existing->fresh()->google_id);
    }
}
