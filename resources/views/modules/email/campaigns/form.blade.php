@extends('layouts.app')

@section('title', $campaign->exists ? 'Edit Campaign' : 'New Campaign')

@section('content')
    @include('modules.email._nav')

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">{{ $campaign->exists ? 'Edit Campaign' : 'Create Campaign' }}</h5>

                    <form method="POST" action="{{ $campaign->exists ? route('email.campaigns.update', $campaign) : route('email.campaigns.store') }}">
                        @csrf
                        @if($campaign->exists) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="name">Campaign name *</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $campaign->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="email_list_id">Audience list *</label>
                                <select name="email_list_id" id="email_list_id" class="form-select @error('email_list_id') is-invalid @enderror" required>
                                    <option value="">Select list</option>
                                    @foreach($lists as $list)
                                        <option value="{{ $list->id }}" @selected((string) old('email_list_id', $campaign->email_list_id) === (string) $list->id)>
                                            {{ $list->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('email_list_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="email_template_id">Template (optional)</label>
                                <select name="email_template_id" id="email_template_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" @selected((string) old('email_template_id', $campaign->email_template_id) === (string) $template->id)>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="subject">Subject *</label>
                                <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror"
                                       value="{{ old('subject', $campaign->subject) }}" required>
                                @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium" for="preview_text">Preview text</label>
                                <input type="text" name="preview_text" id="preview_text" class="form-control"
                                       value="{{ old('preview_text', $campaign->preview_text) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium" for="from_name">From name</label>
                                <input type="text" name="from_name" id="from_name" class="form-control"
                                       value="{{ old('from_name', $campaign->from_name) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium" for="from_email">From email</label>
                                <input type="email" name="from_email" id="from_email" class="form-control"
                                       value="{{ old('from_email', $campaign->from_email) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium" for="reply_to">Reply-to</label>
                                <input type="email" name="reply_to" id="reply_to" class="form-control"
                                       value="{{ old('reply_to', $campaign->reply_to) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium" for="html_body">HTML body *</label>
                                <textarea name="html_body" id="html_body" rows="16"
                                          class="form-control font-monospace @error('html_body') is-invalid @enderror"
                                          required>{{ old('html_body', $campaign->html_body) }}</textarea>
                                @error('html_body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    Tags: <code>@{{first_name}}</code>, <code>@{{email}}</code>, <code>@{{unsubscribe_url}}</code>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium" for="text_body">Plain text</label>
                                <textarea name="text_body" id="text_body" rows="5" class="form-control font-monospace">{{ old('text_body', $campaign->text_body) }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary">Save Campaign</button>
                            <a href="{{ $campaign->exists ? route('email.campaigns.show', $campaign) : route('email.campaigns.index') }}"
                               class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
