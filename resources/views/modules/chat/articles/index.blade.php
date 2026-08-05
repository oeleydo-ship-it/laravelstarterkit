@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Knowledge Base</h4>
        <div class="d-flex gap-2">
            @can('create', App\Models\ChatArticle::class)
                <a href="{{ route('chat.articles.create') }}" class="btn btn-primary">+ New Article</a>
            @endcan
            <a href="{{ route('chat.conversations.index') }}" class="btn btn-outline-secondary">Back to Inbox</a>
        </div>
    </div>

    <form method="GET" action="{{ route('chat.articles.index') }}" class="d-flex gap-2 mb-3" style="max-width: 420px;">
        <input type="search" name="q" class="form-control form-control-sm"
               value="{{ $search }}" placeholder="Search articles…">
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Keywords</th>
                    <th>Status</th>
                    <th>Preview</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td class="fw-medium">{{ $article->title }}</td>
                        <td class="text-muted small">{{ $article->keywords ?: '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $article->is_published ? 'success' : 'secondary' }}">
                                {{ $article->is_published ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ Str::limit($article->body, 80) }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @can('update', $article)
                                    <a href="{{ route('chat.articles.edit', $article) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('delete', $article)
                                    <form method="POST" action="{{ route('chat.articles.destroy', $article) }}"
                                          onsubmit="return confirm('Delete this article?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No articles {{ $search ? 'match that search' : 'yet' }}.
                            @can('create', App\Models\ChatArticle::class)
                                <a href="{{ route('chat.articles.create') }}">Write one</a>.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $articles->appends(request()->query())->links() }}
    </div>
@endsection
