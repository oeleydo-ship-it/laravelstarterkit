@extends('layouts.app')

@section('title', $list->name)

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $list->name }}</h4>
            <p class="text-muted mb-0 small">{{ $list->description ?: 'No description' }}</p>
            <div class="mt-2"><span class="badge bg-success">{{ number_format($list->active_subscribers_count) }} active subscribers</span></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @can('update', $list)
                <a href="{{ route('email.lists.edit', $list) }}" class="btn btn-outline-primary">Edit</a>
            @endcan
            @can('create', App\Models\EmailSubscriber::class)
                <a href="{{ route('email.subscribers.create', ['list_ids' => [$list->id]]) }}" class="btn btn-outline-secondary">Add subscriber</a>
                <a href="{{ route('email.subscribers.import') }}" class="btn btn-outline-secondary">Import CSV</a>
                <form method="POST" action="{{ route('email.subscribers.import-clients') }}">
                    @csrf
                    <input type="hidden" name="list_id" value="{{ $list->id }}">
                    <button class="btn btn-outline-secondary" onclick="return confirm('Import all CRM contacts with emails into this list? Unsubscribed contacts are skipped unless you re-subscribe them later.')">Import from CRM</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr>
                        <td><a href="{{ route('email.subscribers.show', $subscriber) }}">{{ $subscriber->email }}</a></td>
                        <td>{{ $subscriber->fullName() }}</td>
                        <td>
                            @php
                                $pivotStatus = $subscriber->pivot->status ?? $subscriber->status;
                                $isActive = $pivotStatus === 'subscribed';
                            @endphp
                            <span class="badge {{ $isActive ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($pivotStatus) }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ optional($subscriber->pivot->subscribed_at)->format('M d, Y') ?? $subscriber->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No subscribers on this list.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $subscribers->links() }}</div>
@endsection
