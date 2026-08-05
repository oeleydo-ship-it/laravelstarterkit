@extends('layouts.app')

@section('title', 'Subscribers')

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Subscribers</h4>
            <p class="text-muted mb-0 small">Manage contacts across your email lists.</p>
        </div>
        <div class="d-flex gap-2">
            @can('create', App\Models\EmailSubscriber::class)
                <a href="{{ route('email.subscribers.import') }}" class="btn btn-outline-secondary">Import CSV</a>
                <a href="{{ route('email.subscribers.create') }}" class="btn btn-primary">+ Add Subscriber</a>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('email.subscribers.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
            <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="Search email or name…">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="list" class="form-select">
                <option value="">All lists</option>
                @foreach($lists as $list)
                    <option value="{{ $list->id }}" @selected((string) $listId === (string) $list->id)>{{ $list->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary">Filter</button>
        </div>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Lists</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr>
                        <td><a href="{{ route('email.subscribers.show', $subscriber) }}" class="fw-medium text-decoration-none">{{ $subscriber->email }}</a></td>
                        <td>{{ trim(($subscriber->first_name.' '.$subscriber->last_name)) ?: '—' }}</td>
                        <td>
                            @forelse($subscriber->lists as $list)
                                <span class="badge bg-light text-dark border">{{ $list->name }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td><span class="badge {{ $subscriber->statusBadgeClass() }}">{{ $subscriber->statusLabel() }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('update', $subscriber)
                                    <a href="{{ route('email.subscribers.edit', $subscriber) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('delete', $subscriber)
                                    <form method="POST" action="{{ route('email.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('Delete this subscriber?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No subscribers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $subscribers->links() }}</div>
@endsection
