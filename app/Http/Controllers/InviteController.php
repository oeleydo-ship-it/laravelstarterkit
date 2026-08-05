<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class InviteController extends Controller
{
    public function accept(string $token)
    {
        $invite = Invite::where('token', $token)->firstOrFail();

        if ($invite->isAccepted()) {
            return redirect()->route('login')->with('error', 'This invite has already been accepted.');
        }

        if ($invite->isExpired()) {
            return redirect()->route('login')->with('error', 'This invite has expired.');
        }

        // Check if the user already has an account
        $existingUser = User::withoutGlobalScopes()->where('email', $invite->email)->first();

        if ($existingUser) {
            // If user exists but belongs to another tenant, reject
            if ($existingUser->tenant_id && $existingUser->tenant_id !== $invite->tenant_id) {
                return redirect()->route('login')->with('error', 'You already belong to another workspace.');
            }

            // Assign to tenant
            $existingUser->update([
                'tenant_id' => $invite->tenant_id,
                'role' => $invite->role,
                'status' => 'active',
                'privileges' => Privileges::defaultsForRole($invite->role),
            ]);

            $invite->update(['accepted_at' => now()]);

            Auth::login($existingUser);

            return redirect()->route('dashboard')->with('success', 'Welcome to the team!');
        }

        // Show registration form for new users
        return view('auth.accept-invite', compact('invite'));
    }

    public function register(Request $request, string $token)
    {
        $invite = Invite::where('token', $token)->firstOrFail();

        if ($invite->isAccepted() || $invite->isExpired()) {
            return redirect()->route('login')->with('error', 'This invite is no longer valid.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => $request->name,
            'email' => $invite->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $invite->tenant_id,
            'role' => $invite->role,
            'status' => 'active',
            'privileges' => Privileges::defaultsForRole($invite->role),
            'email_verified_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to the team!');
    }
}
