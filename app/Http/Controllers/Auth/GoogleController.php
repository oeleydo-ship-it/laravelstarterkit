<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;
class GoogleController extends Controller {
    public function redirect() {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) return back()->withErrors(['google'=>'Google sign-in has not been configured by the administrator.']);
        return Socialite::driver('google')->redirect();
    }
    public function callback() {
        try { $google=Socialite::driver('google')->user(); } catch (Throwable $e) { report($e); return redirect()->route('login')->withErrors(['google'=>'Google sign-in could not be completed. Please try again.']); }
        if (blank($google->getEmail())) return redirect()->route('login')->withErrors(['google'=>'Google did not provide an email address.']);
        $user=User::withoutGlobalScopes()->where('google_id',$google->getId())->orWhere('email',$google->getEmail())->first();
        if ($user) {
            if ($user->status !== 'active') return redirect()->route('login')->withErrors(['google'=>'This account is inactive. Contact support.']);
            $user->forceFill(['google_id'=>$google->getId(),'avatar_url'=>$google->getAvatar()])->save();
        } else {
            $user=User::withoutGlobalScopes()->create(['name'=>$google->getName() ?: Str::before($google->getEmail(),'@'),'email'=>$google->getEmail(),'google_id'=>$google->getId(),'avatar_url'=>$google->getAvatar(),'password'=>Hash::make(Str::random(48)),'role'=>'owner','status'=>'active','email_verified_at'=>now()]);
        }
        Auth::login($user,true); request()->session()->regenerate();
        if ($user->is_superadmin) return redirect()->route('superadmin.dashboard');
        if ($user->needsOnboarding()) return redirect()->route('onboarding.show');
        return redirect()->intended(route('dashboard'));
    }
}
