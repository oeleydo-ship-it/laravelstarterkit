@extends('layouts.app')

@section('title', $article->exists ? 'Edit Article' : 'New Article')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">{{ $article->exists ? 'Edit Article' : 'New Article' }}</h5>

                <form method="POST"
                      action="{{ $article->exists
                          ? route('chat.articles.update', $article)
                          : route('chat.articles.store') }}">
                    @csrf
                    @if($article->exists) @method('PUT') @endif

                    <div class="mb-3">
                        <label for="title" class="form-label fw-medium">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title', $article->title) }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="keywords" class="form-label fw-medium">Keywords</label>
                        <input type="text" class="form-control @error('keywords') is-invalid @enderror"
                               id="keywords" name="keywords" value="{{ old('keywords', $article->keywords) }}"
                               placeholder="refund, returns, money back">
                        @error('keywords') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            Extra words agents and AI assist can match on when they are not in the article text.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="body" class="form-label fw-medium">Article *</label>
                        <textarea class="form-control @error('body') is-invalid @enderror"
                                  id="body" name="body" rows="10" required>{{ old('body', $article->body) }}</textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1"
                               id="is_published" {{ old('is_published', $article->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">
                            Published — available to agents and AI assist
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            {{ $article->exists ? 'Update Article' : 'Create Article' }}
                        </button>
                        <a href="{{ route('chat.articles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
