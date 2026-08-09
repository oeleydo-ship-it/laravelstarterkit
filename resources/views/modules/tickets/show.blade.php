@extends('layouts.app')

@section('title', $ticket->title)

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="fw-bold mb-1">{{ $ticket->title }}</h5>
                            <div class="d-flex gap-2">
                                @php
                                    $priorityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'danger'];
                                    $statusColors = ['open' => 'info', 'in_progress' => 'warning', 'closed' => 'secondary'];
                                @endphp
                                <span
                                    class="badge bg-{{ $priorityColors[$ticket->priority] ?? 'secondary' }}{{ $ticket->priority === 'medium' ? ' text-dark' : '' }}">{{ ucfirst($ticket->priority) }}</span>
                                <span
                                    class="badge bg-{{ $statusColors[$ticket->status] ?? 'secondary' }}{{ $ticket->status === 'in_progress' ? ' text-dark' : '' }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <form method="POST" action="{{ route('tickets.status.update', $ticket) }}">
                                @csrf @method('PATCH')
                                <label for="quick-status" class="visually-hidden">Update status</label>
                                <select id="quick-status" name="status" class="form-select form-select-sm"
                                    onchange="this.disabled=true; this.form.submit();" aria-label="Update ticket status">
                                    @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'closed' => 'Closed'] as $value => $label)
                                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Assigned To</label>
                            <strong>{{ $ticket->assignee->name ?? 'Unassigned' }}</strong>
                        </div>
                        <div class="col-md-6"><label class="text-muted small d-block">Requester</label><strong>{{ $ticket->requester_name ?? 'Not specified' }}</strong>@if($ticket->requester_email)<div><a href="mailto:{{ $ticket->requester_email }}">{{ $ticket->requester_email }}</a></div>@endif</div>
                        <div class="col-md-6"><label class="text-muted small d-block">Source</label><span>{{ str_replace('_', ' ', ucfirst($ticket->source)) }}@if($ticket->category) · {{ $ticket->category }}@endif</span></div>
                        @if($ticket->conversation)
                            <div class="col-12"><a href="{{ route('chat.conversations.show', $ticket->conversation) }}" class="btn btn-sm btn-outline-primary">Open linked live chat</a></div>
                        @endif
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Created</label>
                            <span>{{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Description</label>
                            <p>{{ $ticket->description ?? 'No description.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card stat-card mt-4"><div class="card-body p-4">
                <h5 class="fw-bold mb-3">Activity</h5>
                @forelse($ticket->replies as $reply)
                    <div class="border rounded p-3 mb-3 {{ $reply->is_internal ? 'bg-warning-subtle' : '' }}"><div class="d-flex justify-content-between mb-2"><strong>{{ $reply->user?->name ?? 'Former agent' }}</strong><span class="text-muted small">{{ $reply->created_at->format('M d, Y h:i A') }}</span></div>@if($reply->is_internal)<span class="badge bg-warning text-dark mb-2">Internal note</span>@endif<p class="mb-0" style="white-space:pre-wrap">{{ $reply->body }}</p></div>
                @empty<p class="text-muted">No replies or notes yet.</p>@endforelse

                <form method="POST" action="{{ route('tickets.replies.store', $ticket) }}" class="mt-4">@csrf
                    <label class="form-label fw-medium">Add reply or note</label><textarea name="body" rows="4" class="form-control mb-2" required></textarea>
                    <div class="d-flex justify-content-between align-items-center"><div class="form-check"><input type="hidden" name="is_internal" value="0"><input class="form-check-input" type="checkbox" name="is_internal" value="1" id="is_internal"><label class="form-check-label" for="is_internal">Internal note</label></div><button class="btn btn-primary">Add activity</button></div>
                </form>
            </div></div>
        </div>
    </div>
@endsection
