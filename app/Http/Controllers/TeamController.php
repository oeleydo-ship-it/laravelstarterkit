<?php

namespace App\Http\Controllers;

use App\Models\Invite;
use App\Models\Module;
use App\Models\Team;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index()
    {
        $tenant = currentTenant();
        $users = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('teams')
            ->orderBy('role')
            ->get();

        $invites = Invite::where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->get();

        $teams = Team::with(['users', 'modules'])
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $modules = Module::orderBy('name')->get();
        $privilegeGroups = Privileges::groups();
        $privilegeLabels = Privileges::all();

        $maxUsers = $tenant->getPlanLimit('max_users', 999);
        $currentCount = $tenant->activeUserCount();

        return view('team.index', compact(
            'users',
            'invites',
            'teams',
            'modules',
            'privilegeGroups',
            'privilegeLabels',
            'maxUsers',
            'currentCount'
        ));
    }

    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,member',
        ]);

        $tenant = currentTenant();

        // Check plan limits
        $maxUsers = $tenant->getPlanLimit('max_users', 999);
        $currentCount = $tenant->activeUserCount();
        $pendingInvites = Invite::where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->count();

        if (($currentCount + $pendingInvites) >= $maxUsers) {
            return redirect()->back()->with('error', "You have reached the maximum of {$maxUsers} users on your current plan.");
        }

        // Check if user already exists in tenant
        $existingUser = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $request->email)
            ->first();

        if ($existingUser) {
            return redirect()->back()->with('error', 'This user is already a member of your workspace.');
        }

        // Check for pending invite
        $existingInvite = Invite::where('tenant_id', $tenant->id)
            ->where('email', $request->email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvite) {
            return redirect()->back()->with('error', 'An invite has already been sent to this email.');
        }

        // Create invite
        $invite = Invite::create([
            'tenant_id' => $tenant->id,
            'email' => $request->email,
            'role' => $request->role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        // In production, send email with: route('invite.accept', $invite->token)
        // For now, flash the link
        $link = route('invite.accept', $invite->token);

        return redirect()->back()->with('success', "Invite sent! Share this link: {$link}");
    }

    public function changeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:owner,admin,member',
        ]);

        $currentUser = auth()->user();
        $tenant = currentTenant();

        // Only owner can change roles
        if (!$currentUser->isOwner()) {
            return redirect()->back()->with('error', 'Only the workspace owner can change roles.');
        }

        // Can't change own role
        if ($user->id === $currentUser->id) {
            return redirect()->back()->with('error', 'You cannot change your own role.');
        }

        // Verify user belongs to same tenant
        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        // If transferring ownership
        if ($request->role === 'owner') {
            $currentUser->update(['role' => 'admin']);
            $currentUser->applyRolePrivilegeDefaults();
        }

        $user->update(['role' => $request->role]);
        $user->applyRolePrivilegeDefaults();

        return redirect()->back()->with('success', 'User role updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $currentUser = auth()->user();
        $tenant = currentTenant();

        if (!$currentUser->isOwnerOrAdmin()) {
            return redirect()->back()->with('error', 'You do not have permission to manage users.');
        }

        if ($user->id === $currentUser->id) {
            return redirect()->back()->with('error', 'You cannot deactivate yourself.');
        }

        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "User {$newStatus} successfully.");
    }
}
