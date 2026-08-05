@extends('layouts.app')

@section('title', $response->exists ? 'Edit Canned Reply' : 'New Canned Reply')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">{{ $response->exists ? 'Edit Canned Reply' : 'New Canned Reply' }}</h5>

                <form method="POST"
                      action="{{ $response->exists
                          ? route('chat.canned-responses.update', $response)
                          : route('chat.canned-responses.store') }}">
                    @csrf
                    @if($response->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="title" class="form-label fw-medium">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title', $response->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="shortcut" class="form-label fw-medium">Shortcut</label>
                        <input type="text" class="form-control @error('shortcut') is-invalid @enderror"
                               id="shortcut" name="shortcut" value="{{ old('shortcut', $response->shortcut) }}"
                               placeholder="/greeting">
                        @error('shortcut') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Optional. Must be unique within your workspace.</div>
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label fw-medium">Message *</label>
                        <textarea class="form-control @error('body') is-invalid @enderror"
                                  id="body" name="body" rows="5" required>{{ old('body', $response->body) }}</textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $response->exists ? 'Update Reply' : 'Create Reply' }}
                        </button>
                        <a href="{{ route('chat.canned-responses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
