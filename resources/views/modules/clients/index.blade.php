@extends('layouts.app')

@section('title', 'CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">CRM</h4>
            <p class="text-muted mb-0 small">Store and manage client information for your workspace.</p>
        </div>
        @can('create', App\Models\Client::class)
            <a href="{{ route('clients.create') }}" class="btn btn-primary">+ New Client</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('clients.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-5">
            <label class="form-label small text-muted mb-1">Search</label>
            <input type="search" name="q" class="form-control" value="{{ $search }}"
                   placeholder="Name, company, email, phone, city…">
        </div>
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">Filter</button>
            @if($search || $status)
                <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Tags</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="text-decoration-none fw-medium">
                                {{ $client->name }}
                            </a>
                        </td>
                        <td>{{ $client->company ?? '—' }}</td>
                        <td>
                            <div>{{ $client->email ?? '—' }}</div>
                            <div class="text-muted small">{{ $client->phone ?? '' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $client->statusBadgeClass() }}">{{ $client->statusLabel() }}</span>
                        </td>
                        <td>
                            @forelse($client->tagList() as $tag)
                                <span class="badge bg-light text-dark border">{{ $tag }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-muted small">{{ $client->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @can('update', $client)
                                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('delete', $client)
                                    <form method="POST" action="{{ route('clients.destroy', $client) }}"
                                        onsubmit="return confirm('Delete this client?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No clients found.
                            @can('create', App\Models\Client::class)
                                <a href="{{ route('clients.create') }}">Add your first client</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $clients->links() }}
    </div>
@endsection
