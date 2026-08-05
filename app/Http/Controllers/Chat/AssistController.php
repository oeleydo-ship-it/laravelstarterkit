<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Chat\AiAssistService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssistController extends Controller
{
    public function __construct(protected AiAssistService $assist)
    {
    }

    public function suggest(ChatConversation $conversation)
    {
        $this->authorize('update', $conversation);

        if (! $this->assist->isAvailable(currentTenant() ?? $conversation->tenant)) {
            return response()->json(['message' => 'AI assist is not configured for this workspace.'], 503);
        }

        try {
            $suggestion = $this->assist->suggestReply($conversation);
        } catch (Throwable $e) {
            // The provider's own message can carry account or billing detail, so
            // it goes to the log and the agent gets a neutral failure.
            Log::warning('Chat AI assist failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'The assistant could not draft a reply right now.'], 502);
        }

        // Returned, never sent: an agent reads and edits it before the visitor
        // sees anything.
        return response()->json(['suggestion' => $suggestion]);
    }
}
