@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
@include('modules.socialproof._nav')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="fw-bold mb-0">Notifications</h4>
    @can('create', App\Models\SocialProofEvent::class)
        <a class="btn btn-primary" href="{{ route('socialproof.events.create') }}">Add notification</a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="source" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All sources</option>
            @foreach(\App\Models\SocialProofEvent::sources() as $value => $label)
                <option value="{{ $value }}" @selected(request('source') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach(\App\Models\SocialProofEvent::types() as $value => $label)
                <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="card">
    <table class="table mb-0 align-middle">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Item</th>
                <th>Type</th>
                <th>Source</th>
                <th>Active</th>
                <th>When</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $event)
                <tr>
                    <td>
                        {{ $event->customer_name }}
                        @if($event->location)
                            <div class="small text-muted">{{ $event->location }}</div>
                        @endif
                    </td>
                    <td>{{ $event->item_name }}</td>
                    <td>{{ ucfirst($event->type) }}</td>
                    <td>{{ ucfirst($event->source) }}</td>
                    <td>
                        <span class="badge {{ $event->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $event->is_active ? 'Yes' : 'No' }}
                        </span>
                    </td>
                    <td>{{ optional($event->occurred_at)->format('M j, Y H:i') }}</td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('socialproof.events.edit', $event) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No notifications yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $events->links() }}
@endsection
