@extends('layouts.app')

@section('title', $template->name)

@section('content')
    @include('modules.email._nav')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $template->name }}</h4>
            <p class="text-muted mb-0 small">Subject: {{ $template->subject }}</p>
        </div>
        <div class="d-flex gap-2">
            @can('update', $template)
                <a href="{{ route('email.templates.edit', $template) }}" class="btn btn-outline-primary">Edit</a>
            @endcan
            <a href="{{ route('email.templates.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card stat-card">
        <div class="card-body p-0">
            <iframe title="Template preview" style="width:100%;min-height:480px;border:0;"
                    srcdoc="{{ e($template->html_body) }}"></iframe>
        </div>
    </div>
@endsection
