@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Support Tickets</h4>
        <a href="{{ route('tickets.create') }}" class="btn btn-primary">+ New Ticket</a>
    </div>

    {{-- Filters --}}
    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('tickets.index') }}"
            class="btn btn-sm {{ !request('status') && !request('priority') ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
        <a href="{{ route('tickets.index', ['status' => 'open']) }}"
            class="btn btn-sm {{ request('status') === 'open' ? 'btn-primary' : 'btn-outline-secondary' }}">Open</a>
        <a href="{{ route('tickets.index', ['status' => 'in_progress']) }}"
            class="btn btn-sm {{ request('status') === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' }}">In
            Progress</a>
        <a href="{{ route('tickets.index', ['status' => 'closed']) }}"
            class="btn btn-sm {{ request('status') === 'closed' ? 'btn-primary' : 'btn-outline-secondary' }}">Closed</a>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td><a href="{{ route('tickets.show', $ticket) }}"
                                class="text-decoration-none fw-medium">{{ $ticket->title }}</a></td>
                        <td>
                            @php
                                $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
                            @endphp
                            <span
                                class="badge bg-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}{{ $ticket->priority === 'medium' ? ' text-dark' : '' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusColors = ['open' => 'info', 'in_progress' => 'warning', 'closed' => 'secondary'];
                            @endphp
                            <span
                                class="badge bg-{{ $statusColors[$ticket->status] ?? 'secondary' }}{{ $ticket->status === 'in_progress' ? ' text-dark' : '' }}">
                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                            </span>
                        </td>
                        <td>{{ $ticket->assignee->name ?? '—' }}</td>
                        <td class="text-muted small">{{ $ticket->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if(auth()->user()->isOwnerOrAdmin())
                                    <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                                        onsubmit="return confirm('Delete this ticket?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No tickets yet. <a
                                href="{{ route('tickets.create') }}">Create your first ticket</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $tickets->appends(request()->query())->links() }}
    </div>
@endsection