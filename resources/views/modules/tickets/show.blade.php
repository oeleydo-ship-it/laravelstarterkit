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
                            <a href="{{ route('tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Assigned To</label>
                            <strong>{{ $ticket->assignee->name ?? 'Unassigned' }}</strong>
                        </div>
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
        </div>
    </div>
@endsection