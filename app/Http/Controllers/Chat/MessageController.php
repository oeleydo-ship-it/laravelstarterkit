<?php

namespace App\Http\Controllers\Chat;

use App\Events\ChatTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatMessageRequest;
use App\Models\ChatConversation;
use App\Services\Chat\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected MessageService $messages)
    {
    }

    public function index(ChatConversation $conversation)
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with(['sender', 'attachment'])
            ->orderBy('id')
            ->get()
            ->map(function ($message) {
                if ($message->is_internal) {
                    return [
                        'id' => $message->id,
                        'type' => 'note',
                        'author_name' => $message->sender?->name ?? 'Agent',
                        'body' => $message->body,
                        'created_at' => $message->created_at?->toIso8601String(),
                    ];
                }

                return [
                    'id' => $message->id,
                    'type' => 'message',
                    'sender_type' => $message->sender_type,
                    'sender_name' => match ($message->sender_type) {
                        'bot' => 'Assistant',
                        'agent' => $message->sender?->name ?? 'Support',
                        default => 'Visitor',
                    },
                    'body' => $message->body,
                    'attachment' => $message->attachment?->toPayload(),
                    'download_url' => $message->attachment
                        ? route('chat.attachments.download', $message->attachment)
                        : null,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json(['data' => $messages]);
    }

    public function store(SendChatMessageRequest $request, ChatConversation $conversation)
    {
        $this->authorize('reply', $conversation);

        $message = $this->messages->sendAsAgent($conversation, $request->user(), $request->validated('body'));

        return response()->json($message->load('sender'), 201);
    }

    public function note(SendChatMessageRequest $request, ChatConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $note = $this->messages->addInternalNote($conversation, $request->user(), $request->validated('body'));

        return response()->json([
            'id' => $note->id,
            'author_name' => $request->user()->name,
            'body' => $note->body,
            'created_at' => $note->created_at?->toIso8601String(),
        ], 201);
    }

    public function read(Request $request, ChatConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $this->messages->markRead($conversation, 'agent');

        return response()->noContent();
    }

    public function typing(Request $request, ChatConversation $conversation)
    {
        $this->authorize('reply', $conversation);

        broadcast(new ChatTyping($conversation->tenant_id, $conversation->id, 'agent', $request->user()->name))->toOthers();

        return response()->noContent();
    }
}
