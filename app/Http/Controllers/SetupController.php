<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        return view('setup.create', [
            'setupToken' => $this->makeSetupToken(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isInstalled()) {
            return redirect()->route('login');
        }

        abort_unless($this->validSetupToken($request->string('setup_token')->toString()), 419);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            // Serialize competing first-run submissions. The second request is
            // rejected instead of silently creating another system administrator.
            if (User::withoutGlobalScopes()->lockForUpdate()->where('is_superadmin', true)->exists()) {
                abort(409, 'The application has already been installed.');
            }

            return User::withoutGlobalScopes()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'tenant_id' => null,
                'role' => null,
                'status' => 'active',
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('superadmin.dashboard')
            ->with('success', 'Super administrator account created successfully.');
    }

    private function isInstalled(): bool
    {
        return User::withoutGlobalScopes()
            ->where('is_superadmin', true)
            ->exists();
    }

    private function makeSetupToken(): string
    {
        $timestamp = (string) now()->timestamp;

        return $timestamp.'.'.hash_hmac('sha256', 'superadmin-setup|'.$timestamp, (string) config('app.key'));
    }

    private function validSetupToken(string $token): bool
    {
        [$timestamp, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if (! ctype_digit((string) $timestamp) || ! is_string($signature)) {
            return false;
        }

        $age = now()->timestamp - (int) $timestamp;
        $expected = hash_hmac('sha256', 'superadmin-setup|'.$timestamp, (string) config('app.key'));

        return $age >= 0 && $age <= 1800 && hash_equals($expected, $signature);
    }
}
