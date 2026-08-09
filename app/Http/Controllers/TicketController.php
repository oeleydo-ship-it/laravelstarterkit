<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Models\ChatConversation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketFromConversationService;
use App\Services\TicketSettingsService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ticket::class, 'ticket');
    }

    public function index(Request $request)
    {
        $query = Ticket::with('assignee');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderByDesc('created_at')->paginate(15);

        return view('modules.tickets.index', compact('tickets'));
    }

    public function create(Request $request, TicketSettingsService $settings)
    {
        $users = User::where('status', 'active')->get();
        $ticket = new Ticket([
            'priority' => $settings->for(currentTenant())['default_priority'],
            'status' => 'open',
        ]);

        if ($request->filled('conversation')) {
            $conversation = ChatConversation::with(['visitor', 'messages'])->findOrFail($request->integer('conversation'));
            $ticket->fill([
                'chat_conversation_id' => $conversation->id,
                'title' => 'Support request from '.($conversation->visitor?->name ?: 'chat visitor'),
                'description' => $conversation->messages->where('is_internal', false)
                    ->map(fn ($message) => ucfirst($message->sender_type).': '.$message->body)->implode("\n\n"),
                'requester_name' => $conversation->visitor?->name,
                'requester_email' => $conversation->visitor?->email,
                'assigned_to' => $conversation->assigned_to,
            ]);
        }

        return view('modules.tickets.create', compact('users', 'ticket'));
    }

    public function store(TicketRequest $request)
    {
        $data = $request->validated();
        if (filled($data['chat_conversation_id'] ?? null)) {
            ChatConversation::findOrFail($data['chat_conversation_id']);
            $data['source'] = 'live_chat';
        }
        Ticket::create($data);

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['assignee', 'conversation.visitor', 'replies.user']);

        return view('modules.tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $users = User::where('status', 'active')->get();

        return view('modules.tickets.edit', compact('ticket', 'users'));
    }

    public function update(TicketRequest $request, Ticket $ticket)
    {
        $data = $request->validated();
        $data['resolved_at'] = $data['status'] === 'closed' ? ($ticket->resolved_at ?? now()) : null;
        $ticket->update($data);

        return redirect()->route('tickets.index')->with('success', 'Ticket updated successfully.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $validated = $request->validate(['body' => 'required|string|max:10000', 'is_internal' => 'nullable|boolean']);
        $ticket->replies()->create([
            'tenant_id' => $ticket->tenant_id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        return back()->with('success', $request->boolean('is_internal') ? 'Internal note added.' : 'Reply added.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,closed',
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'closed' ? ($ticket->resolved_at ?? now()) : null,
        ]);

        return back()->with('success', 'Ticket status updated.');
    }

    public function fromConversation(Request $request, ChatConversation $conversation, TicketFromConversationService $service)
    {
        $this->authorize('create', Ticket::class);
        abort_unless(currentTenant()->isModuleEnabled('tickets'), 403, 'The Tickets module is disabled.');
        abort_if($conversation->tickets()->exists(), 422, 'This conversation already has a ticket.');

        $ticket = $request->string('mode')->toString() === 'ai'
            ? $service->createByAi($conversation)
            : $service->createByAgent($conversation);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created from live chat.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket deleted successfully.');
    }
}
