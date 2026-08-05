@extends('layouts.app')

@section('title', 'New Ticket')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card stat-card">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4">Create New Ticket</h5>

                <form method="POST" action="{{ route('tickets.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label fw-medium">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror"
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-medium">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror"
                                  id="description" name="description" rows="4">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="priority" class="form-label fw-medium">Priority *</label>
                            <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                @foreach(['low', 'medium', 'high', 'urgent'] as $p)
                                    <option value="{{ $p }}" {{ old('priority', 'medium') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label fw-medium">Status *</label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach(['open', 'in_progress', 'closed'] as $s)
                                    <option value="{{ $s }}" {{ old('status', 'open') === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="assigned_to" class="form-label fw-medium">Assign To</label>
                            <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Ticket</button>
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection