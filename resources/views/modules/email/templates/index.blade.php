@extends('layouts.app')

@section('title', 'Templates')

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Templates</h4>
            <p class="text-muted mb-0 small">Reusable HTML email layouts for campaigns.</p>
        </div>
        @can('create', App\Models\EmailTemplate::class)
            <a href="{{ route('email.templates.create') }}" class="btn btn-primary">+ New Template</a>
        @endcan
    </div>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-6">
            <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search templates…">
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary">Filter</button></div>
    </form>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td class="fw-medium">{{ $template->name }}</td>
                        <td>{{ $template->subject }}</td>
                        <td class="text-muted small">{{ $template->updated_at->format('M d, Y') }}</td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('email.templates.show', $template) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                                @can('update', $template)
                                    <a href="{{ route('email.templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @endcan
                                @can('create', App\Models\EmailCampaign::class)
                                    <a href="{{ route('email.campaigns.create', ['template' => $template->id]) }}" class="btn btn-sm btn-outline-success">Use</a>
                                @endcan
                                @can('delete', $template)
                                    <form method="POST" action="{{ route('email.templates.destroy', $template) }}" onsubmit="return confirm('Delete template?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No templates yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $templates->links() }}</div>
@endsection
