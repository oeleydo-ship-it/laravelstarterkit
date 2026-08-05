@extends('layouts.app')

@section('title', $list->exists ? 'Edit List' : 'New List')

@section('content')
    @include('modules.email._nav')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">{{ $list->exists ? 'Edit List' : 'Create List' }}</h5>
                    <form method="POST" action="{{ $list->exists ? route('email.lists.update', $list) : route('email.lists.store') }}">
                        @csrf
                        @if($list->exists) @method('PUT') @endif

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="name">Name *</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $list->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium" for="description">Description</label>
                            <textarea name="description" id="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $list->description) }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">{{ $list->exists ? 'Update List' : 'Create List' }}</button>
                            <a href="{{ $list->exists ? route('email.lists.show', $list) : route('email.lists.index') }}"
                               class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
