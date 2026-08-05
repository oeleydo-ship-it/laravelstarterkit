@extends('layouts.app')

@section('title', 'Canned Replies')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Canned Replies</h4>
        <div class="d-flex gap-2">
            @can('create', App\Models\ChatCannedResponse::class)
                <a href="{{ route('chat.canned-responses.create') }}" class="btn btn-primary">+ New Reply</a>
            @endcan
            <a href="{{ route('chat.conversations.index') }}" class="btn btn-outline-secondary">Back to Inbox</a>
        </div>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Shortcut</th>
                    <th>Message</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($responses as $response)
                    <tr>
                        <td class="fw-medium">{{ $response->title }}</td>
                        <td>
                            @if($response->shortcut)
                                <code>{{ $response->shortcut }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ Str::limit($response->body, 80) }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('update', $response)
                                    <a href="{{ route('chat.canned-responses.edit', $response) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('delete', $response)
                                    <form method="POST" action="{{ route('chat.canned-responses.destroy', $response) }}"
                                          onsubmit="return confirm('Delete this canned reply?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            No canned replies yet.
                            @can('create', App\Models\ChatCannedResponse::class)
                                <a href="{{ route('chat.canned-responses.create') }}">Create your first one</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $responses->links() }}
    </div>
@endsection
