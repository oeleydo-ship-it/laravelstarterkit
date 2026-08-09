<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TicketSettingsService;
use Illuminate\Http\Request;

class TicketSettingsController extends Controller
{
    public function edit(TicketSettingsService $settings)
    {
        $this->authorize('create', \App\Models\Ticket::class);

        return view('modules.tickets.settings', [
            'settings' => $settings->for(currentTenant()),
            'users' => User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, TicketSettingsService $settings)
    {
        abort_unless($request->user()->isOwnerOrAdmin() || $request->user()->hasPrivilege(\App\Support\Privileges::TICKETS_MANAGE), 403);
        $validated = $request->validate([
            'default_priority' => 'required|in:low,medium,high,urgent',
            'default_assignee_id' => 'nullable|exists:users,id',
            'ai_creation_enabled' => 'nullable|boolean',
            'ai_auto_create_on_close' => 'nullable|boolean',
        ]);
        $validated['ai_creation_enabled'] = $request->boolean('ai_creation_enabled');
        $validated['ai_auto_create_on_close'] = $request->boolean('ai_auto_create_on_close');
        $settings->save(currentTenant(), $validated);

        return back()->with('success', 'Ticket settings updated.');
    }
}
