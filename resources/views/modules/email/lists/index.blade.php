@extends('layouts.app')

@section('title', 'Email Lists')

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Lists</h4>
            <p class="text-muted mb-0 small">Audience segments for your campaigns.</p>
        </div>
        @can('create', App\Models\EmailList::class)
            <a href="{{ route('email.lists.create') }}" class="btn btn-primary">+ New List</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('email.lists.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-6">
            <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="Search lists…">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-primary">Filter</button>
            @if($search)
                <a href="{{ route('email.lists.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </div>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subscribers</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lists as $list)
                    <tr>
                        <td>
                            <a href="{{ route('email.lists.show', $list) }}" class="fw-medium text-decoration-none">{{ $list->name }}</a>
                            @if($list->description)
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($list->description, 80) }}</div>
                            @endif
                        </td>
                        <td>{{ number_format($list->active_subscribers_count) }}</td>
                        <td class="text-muted small">{{ $list->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('email.lists.show', $list) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @can('update', $list)
                                    <a href="{{ route('email.lists.edit', $list) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('delete', $list)
                                    <form method="POST" action="{{ route('email.lists.destroy', $list) }}" onsubmit="return confirm('Delete this list?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No lists yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $lists->links() }}</div>
@endsection
