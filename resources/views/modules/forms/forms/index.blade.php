@extends('layouts.app')

@section('title', 'Forms')

@section('content')
    @include('modules.forms._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Forms</h4>
            <p class="text-muted small mb-0">Lead forms, surveys, quizzes, and NPS.</p>
        </div>
        @can('create', App\Models\Form::class)
            <a class="btn btn-primary" href="{{ route('forms.forms.create') }}">New from template</a>
        @endcan
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Submissions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($forms as $form)
                    <tr>
                        <td class="fw-medium">{{ $form->name }}</td>
                        <td>{{ $form->typeLabel() }}</td>
                        <td><span class="badge {{ $form->statusBadgeClass() }}">{{ $form->statusLabel() }}</span></td>
                        <td>{{ $form->submissions_count }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('forms.forms.edit', $form) }}">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No forms yet.
                            @can('create', App\Models\Form::class)
                                <a href="{{ route('forms.forms.create') }}">Create one</a>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">{{ $forms->links() }}</div>
@endsection
