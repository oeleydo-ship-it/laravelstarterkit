<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Team;
use App\Models\User;
use App\Support\Privileges;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceTeamController extends Controller
{
    public function store(Request $request)
    {
        $this->authorizeManage();

        $tenant = currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'module_keys' => 'nullable|array',
            'module_keys.*' => Rule::exists('modules', 'key'),
            'user_ids' => 'nullable|array',
            'user_ids.*' => Rule::exists('users', 'id')->where(
                fn ($q) => $q->where('tenant_id', $tenant->id)->where('status', 'active')
            ),
        ]);

        $team = Team::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $team->syncModules($validated['module_keys'] ?? []);
        $team->users()->sync($validated['user_ids'] ?? []);

        return redirect()->route('team.index')->with('success', "Team \"{$team->name}\" created.");
    }

    public function update(Request $request, Team $team)
    {
        $this->authorizeManage();
        $this->assertSameTenant($team);

        $tenant = currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'module_keys' => 'nullable|array',
            'module_keys.*' => Rule::exists('modules', 'key'),
            'user_ids' => 'nullable|array',
            'user_ids.*' => Rule::exists('users', 'id')->where(
                fn ($q) => $q->where('tenant_id', $tenant->id)->where('status', 'active')
            ),
        ]);

        $team->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $team->syncModules($validated['module_keys'] ?? []);
        $team->users()->sync($validated['user_ids'] ?? []);

        return redirect()->route('team.index')->with('success', "Team \"{$team->name}\" updated.");
    }

    public function destroy(Team $team)
    {
        $this->authorizeManage();
        $this->assertSameTenant($team);

        $name = $team->name;
        $team->delete();

        return redirect()->route('team.index')->with('success', "Team \"{$name}\" deleted.");
    }

    public function updatePrivileges(Request $request, User $user)
    {
        $this->authorizeManage();

        $currentUser = $request->user();
        $tenant = currentTenant();

        if ($user->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($user->isOwner()) {
            return redirect()->back()->with('error', 'Owner privileges cannot be restricted.');
        }

        if ($user->id === $currentUser->id && ! $currentUser->isOwner()) {
            return redirect()->back()->with('error', 'You cannot change your own privileges.');
        }

        $validated = $request->validate([
            'privileges' => 'nullable|array',
            'privileges.*' => Rule::in(Privileges::keys()),
        ]);

        // Admins cannot grant billing or team-manage unless they are owner.
        $privileges = $validated['privileges'] ?? [];
        if (! $currentUser->isOwner()) {
            $privileges = array_values(array_diff($privileges, [
                Privileges::BILLING_MANAGE,
                Privileges::TEAM_MANAGE,
            ]));
        }

        $user->syncPrivileges($privileges);

        return redirect()->back()->with('success', "Privileges updated for {$user->name}.");
    }

    protected function authorizeManage(): void
    {
        $user = auth()->user();

        if (! $user || (! $user->isOwnerOrAdmin() && ! $user->hasPrivilege(Privileges::TEAM_MANAGE))) {
            abort(403, 'You do not have permission to manage teams.');
        }
    }

    protected function assertSameTenant(Team $team): void
    {
        if ($team->tenant_id !== currentTenant()->id) {
            abort(403);
        }
    }
}
