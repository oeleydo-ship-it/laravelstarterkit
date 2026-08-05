<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferChatConversationRequest;
use App\Http\Requests\UpdateChatConversationRequest;
use App\Http\Requests\UpdateChatVisitorRequest;
use App\Models\ChatCannedResponse;
use App\Models\ChatConversation;
use App\Models\User;
use App\Services\Chat\AiAssistService;
use App\Services\Chat\ConversationService;
use App\Services\Chat\VisitorCrmSyncService;
use App\Support\ChatInboxPayload;
use App\Support\LikeSearch;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(protected ConversationService $conversations)
    {
        $this->authorizeResource(ChatConversation::class, 'conversation');
    }

    public function index(Request $request)
    {
        $query = ChatConversation::with(['visitor', 'assignee']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->open();
        }

        match ($request->query('filter')) {
            'mine' => $query->where('assigned_to', $request->user()->id),
            'unassigned' => $query->whereNull('assigned_to'),
            'rated' => $query->rated(),
            default => null,
        };

        // Search spans who the visitor is and what was said, because an agent
        // looking for a past chat remembers one or the other, rarely both.
        // Internal notes are searchable too — they are staff-facing already.
        if (filled($search = $request->query('q'))) {
            $needle = LikeSearch::pattern($search);

            $query->where(function ($outer) use ($needle) {
                $outer->whereHas('visitor', fn ($visitor) => $visitor
                    ->whereRaw(LikeSearch::clause('name'), [$needle])
                    ->orWhereRaw(LikeSearch::clause('email'), [$needle]))
                    ->orWhereHas('messages', fn ($messages) => $messages
                        ->whereRaw(LikeSearch::clause('body'), [$needle]))
                    ->orWhereRaw(LikeSearch::clause('rating_comment'), [$needle]);
            });
        }

        // Unread = visitor messages nobody on the team has read yet.
        $query->withCount(['messages as unread_count' => function ($messages) {
            $messages->where('sender_type', 'visitor')->whereNull('read_at');
        }]);

        $conversations = $query->orderByDesc('last_message_at')->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $conversations->getCollection()
                    ->map(fn (ChatConversation $conversation) => ChatInboxPayload::from($conversation, $request->user()))
                    ->values(),
            ]);
        }

        return view('modules.chat.inbox', [
            'conversations' => $conversations,
            'search' => $request->query('q'),
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $conversation->load(['visitor.client', 'assignee', 'messages.sender', 'messages.attachment']);

        $users = User::chatAgents()->orderBy('name')->get();
        $cannedResponses = ChatCannedResponse::orderBy('title')->get();
        $aiAvailable = app(AiAssistService::class)->isAvailable(currentTenant());

        $sidebarConversations = ChatConversation::with(['visitor', 'assignee'])
            ->open()
            ->withCount(['messages as unread_count' => function ($messages) {
                $messages->where('sender_type', 'visitor')->whereNull('read_at');
            }])
            ->orderByDesc('last_message_at')
            ->limit(40)
            ->get();

        return view('modules.chat.conversation', compact(
            'conversation',
            'users',
            'cannedResponses',
            'aiAvailable',
            'sidebarConversations',
        ));
    }

    public function updateVisitor(UpdateChatVisitorRequest $request, ChatConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $data = $request->validated();
        $visitor = $conversation->visitor;
        $visitor->update($data);

        $client = app(VisitorCrmSyncService::class)->sync($visitor->fresh(), $data, $request->user());

        return back()->with('success', "Customer info saved to CRM ({$client->name}).");
    }

    public function update(UpdateChatConversationRequest $request, ChatConversation $conversation)
    {
        match ($request->validated('action')) {
            'assign' => $this->conversations->assign($conversation, User::findOrFail($request->validated('assigned_to'))),
            'unassign' => $this->conversations->assign($conversation, null),
            'accept' => $this->conversations->assign($conversation, $request->user()),
            'close' => $this->conversations->close($conversation),
            'reopen' => $this->conversations->reopen($conversation),
        };

        $message = match ($request->validated('action')) {
            'accept' => 'Chat accepted. You can now reply to the visitor.',
            default => 'Conversation updated.',
        };

        return back()->with('success', $message);
    }

    public function transfer(TransferChatConversationRequest $request, ChatConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $this->conversations->transfer(
            $conversation,
            $request->user(),
            User::findOrFail($request->validated('to')),
            $request->validated('reason'),
        );

        return back()->with('success', 'Conversation transferred.');
    }

    public function destroy(ChatConversation $conversation)
    {
        $this->conversations->delete($conversation);

        return redirect()->route('chat.conversations.index')->with('success', 'Conversation deleted.');
    }
}
