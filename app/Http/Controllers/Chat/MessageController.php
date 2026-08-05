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
