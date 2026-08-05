@extends('layouts.app')

@section('title', $client->name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold mb-0">{{ $client->name }}</h4>
                <span class="badge {{ $client->statusBadgeClass() }}">{{ $client->statusLabel() }}</span>
            </div>
            @if($client->company)
                <p class="text-muted mb-0">{{ $client->company }}</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            @can('update', $client)
                <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            @endcan
            <a href="{{ route('clients.index') }}" class="btn btn-sm btn-outline-secondary">Back to CRM</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card stat-card mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Contact details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Email</label>
                            <strong>{{ $client->email ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Phone</label>
                            <strong>{{ $client->phone ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Website</label>
                            @if($client->website)
                                <a href="{{ $client->website }}" target="_blank" rel="noopener">{{ $client->website }}</a>
                            @else
                                <strong>—</strong>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Source</label>
                            <strong>{{ $client->source ?? '—' }}</strong>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Address</label>
                            <strong>
                                {{ collect([$client->address, $client->city, $client->country])->filter()->implode(', ') ?: '—' }}
                            </strong>
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Tags</label>
                            @forelse($client->tagList() as $tag)
                                <span class="badge bg-light text-dark border">{{ $tag }}</span>
                            @empty
                                <span class="text-muted">No tags</span>
                            @endforelse
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Profile notes</label>
                            <p class="mb-0">{{ $client->notes ?: 'No profile notes.' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Created</label>
                            <span>{{ $client->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Updated</label>
                            <span>{{ $client->updated_at->format('M d, Y h:i A') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Activity notes</h6>

                    @can('update', $client)
                        <form method="POST" action="{{ route('clients.notes.store', $client) }}" class="mb-4">
                            @csrf
                            <textarea name="body" class="form-control mb-2 @error('body') is-invalid @enderror" rows="3"
                                      placeholder="Add a CRM note…" required>{{ old('body') }}</textarea>
                            @error('body') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-sm btn-primary">Add note</button>
                        </form>
                    @endcan

                    @forelse($client->crmNotes as $note)
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <strong class="small">{{ $note->author?->name ?? 'Team member' }}</strong>
                                <span class="text-muted small">{{ $note->created_at->diffForHumans() }}</span>
                            </div>
                            <div style="white-space:pre-wrap;">{{ $note->body }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0 small">No activity notes yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
