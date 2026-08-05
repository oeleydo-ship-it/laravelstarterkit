<?php

namespace App\Http\Controllers\Chat;

use App\Events\AgentAvailabilityChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChatAvailabilityRequest;

class AvailabilityController extends Controller
{
    public function update(UpdateChatAvailabilityRequest $request)
    {
        $agent = $request->user();

        if (! $agent->canActAsChatAgent()) {
            abort(403, 'You are not permitted to act as a live chat agent.');
        }

        $agent->update([
            'chat_availability' => $request->validated('availability'),
            'chat_last_seen_at' => now(),
        ]);

        broadcast(new AgentAvailabilityChanged($agent))->toOthers();

        if ($request->expectsJson()) {
            return response()->json(['availability' => $agent->chat_availability]);
        }

        return back()->with('success', 'Chat availability updated.');
    }
}
