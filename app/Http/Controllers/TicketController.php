<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Models\Ticket;
use App\Models\User;
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

    public function create()
    {
        $users = User::where('status', 'active')->get();

        return view('modules.tickets.create', compact('users'));
    }

    public function store(TicketRequest $request)
    {
        Ticket::create($request->validated());

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('assignee');

        return view('modules.tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        $users = User::where('status', 'active')->get();

        return view('modules.tickets.edit', compact('ticket', 'users'));
    }

    public function update(TicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());

        return redirect()->route('tickets.index')->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')->with('success', 'Ticket deleted successfully.');
    }
}
