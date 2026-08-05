@extends('layouts.app')

@section('title', $subscriber->exists ? 'Edit Subscriber' : 'Add Subscriber')

@section('content')
    @include('modules.email._nav')

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">{{ $subscriber->exists ? 'Edit Subscriber' : 'Add Subscriber' }}</h5>
                    <form method="POST" action="{{ $subscriber->exists ? route('email.subscribers.update', $subscriber) : route('email.subscribers.store') }}">
                        @csrf
                        @if($subscriber->exists) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="email">Email *</label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $subscriber->email) }}" required>
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="status">Status *</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $subscriber->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="first_name">First name</label>
                                <input type="text" name="first_name" id="first_name" class="form-control"
                                       value="{{ old('first_name', $subscriber->first_name) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium" for="last_name">Last name</label>
                                <input type="text" name="last_name" id="last_name" class="form-control"
                                       value="{{ old('last_name', $subscriber->last_name) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Lists</label>
                                <div class="row g-2">
                                    @forelse($lists as $list)
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="list_ids[]"
                                                       value="{{ $list->id }}" id="list_{{ $list->id }}"
                                                       @checked(collect(old('list_ids', $selectedListIds))->map(fn ($id) => (int) $id)->contains($list->id))>
                                                <label class="form-check-label" for="list_{{ $list->id }}">{{ $list->name }}</label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-muted small">Create a list first.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary">{{ $subscriber->exists ? 'Update' : 'Add Subscriber' }}</button>
                            <a href="{{ route('email.subscribers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
