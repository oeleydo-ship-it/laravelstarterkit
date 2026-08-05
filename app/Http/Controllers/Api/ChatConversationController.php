<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Http\Request;

class ChatConversationController extends Controller
{
    public function __construct(
        protected ConversationService $conversations,
        protected MessageService $messages,
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,closed',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $conversations = ChatConversation::with(['visitor', 'assignee'])
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('last_message_at')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => $conversations->getCollection()->map(fn ($c) => $this->present($c))->all(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $this->authorizeTenant($conversation);

        $conversation->load(['visitor', 'assignee']);

        return response()->json([
            'data' => $this->present($conversation) + [
                'messages' => $conversation->messages()
                    ->where('is_internal', false)
                    ->with('sender')
                    ->get()
                    ->map(fn (ChatMessage $m) => $this->presentMessage($m))
                    ->all(),
            ],
        ]);
    }

    public function storeMessage(Request $request, ChatConversation $conversation)
    {
        $this->authorizeTenant($conversation);

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            // Scoped to the token's own tenant so the API cannot post as a user
            // from another workspace.
            'agent_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($conversation) {
                    $exists = User::withoutGlobalScopes()
                        ->where('id', $value)
                        ->where('tenant_id', $conversation->tenant_id)
                        ->exists();

                    if (! $exists) {
                        $fail('The selected agent does not belong to this workspace.');
                    }
                },
            ],
        ]);

        $agent = isset($validated['agent_id'])
            ? User::withoutGlobalScopes()->find($validated['agent_id'])
            : User::withoutGlobalScopes()
                ->where('tenant_id', $conversation->tenant_id)
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

        if (! $agent) {
            return response()->json(['message' => 'This workspace has no active agent to send as.'], 422);
        }

        $message = $this->messages->sendAsAgent($conversation, $agent, $validated['body']);

        return response()->json(['data' => $this->presentMessage($message->load('sender'))], 201);
    }

    public function close(ChatConversation $conversation)
    {
        $this->authorizeTenant($conversation);

        $this->conversations->close($conversation);

        return response()->json(['data' => $this->present($conversation->fresh()->load(['visitor', 'assignee']))]);
    }

    /**
     * Route model binding resolves before the API token has bound a tenant, so
     * ownership is checked here rather than being left to the global scope.
     */
    protected function authorizeTenant(ChatConversation $conversation): void
    {
        abort_if($conversation->tenant_id !== currentTenant()?->id, 404);
    }

    protected function present(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'status' => $conversation->status,
            'visitor' => [
                'id' => $conversation->chat_visitor_id,
                'name' => $conversation->visitor?->name,
                'email' => $conversation->visitor?->email,
            ],
            'assigned_to' => $conversation->assignee?->name,
            'last_message_preview' => $conversation->last_message_preview,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $conversation->created_at?->toIso8601String(),
            'closed_at' => $conversation->closed_at?->toIso8601String(),
            'rating' => $conversation->rating,
            'rating_comment' => $conversation->rating_comment,
            'rated_at' => $conversation->rated_at?->toIso8601String(),
        ];
    }

    protected function presentMessage(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => match ($message->sender_type) {
                'bot' => 'Assistant',
                'agent' => $message->sender?->name,
                default => null,
            },
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
