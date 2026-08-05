@extends('layouts.app')

@section('title', $template->exists ? 'Edit Template' : 'New Template')

@section('content')
    @include('modules.email._nav')

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2">{{ $template->exists ? 'Edit Template' : 'Create Template' }}</h5>
                    <p class="text-muted small mb-4">
                        Merge tags: <code>@{{first_name}}</code>, <code>@{{last_name}}</code>,
                        <code>@{{email}}</code>, <code>@{{full_name}}</code>,
                        <code>@{{unsubscribe_url}}</code>
                    </p>

                    <form method="POST" action="{{ $template->exists ? route('email.templates.update', $template) : route('email.templates.store') }}">
                        @csrf
                        @if($template->exists) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="name">Name *</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $template->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="subject">Subject *</label>
                                <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $template->subject) }}" required>
                                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium" for="html_body">HTML body *</label>
                                <textarea name="html_body" id="html_body" rows="16"
                                          class="form-control font-monospace @error('html_body') is-invalid @enderror"
                                          required>{{ old('html_body', $template->html_body) }}</textarea>
                                @error('html_body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium" for="text_body">Plain text (optional)</label>
                                <textarea name="text_body" id="text_body" rows="6"
                                          class="form-control font-monospace">{{ old('text_body', $template->text_body) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary">Save Template</button>
                            <a href="{{ route('email.templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
