@extends('layouts.app')

@section('title', $client->exists ? 'Edit Client' : 'New Client')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">{{ $client->exists ? 'Edit Client' : 'Create New Client' }}</h5>

                    <form method="POST"
                        action="{{ $client->exists ? route('clients.update', $client) : route('clients.store') }}">
                        @csrf
                        @if($client->exists) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-medium">Contact name *</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                    name="name" value="{{ old('name', $client->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="company" class="form-label fw-medium">Company</label>
                                <input type="text" class="form-control @error('company') is-invalid @enderror" id="company"
                                    name="company" value="{{ old('company', $client->company) }}">
                                @error('company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-medium">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                                    name="email" value="{{ old('email', $client->email) }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-medium">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone"
                                    name="phone" value="{{ old('phone', $client->phone) }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label fw-medium">Status *</label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $client->status) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="source" class="form-label fw-medium">Source</label>
                                <input type="text" class="form-control @error('source') is-invalid @enderror" id="source"
                                    name="source" value="{{ old('source', $client->source) }}"
                                    placeholder="Referral, website, ads…">
                                @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="website" class="form-label fw-medium">Website</label>
                                <input type="url" class="form-control @error('website') is-invalid @enderror" id="website"
                                    name="website" value="{{ old('website', $client->website) }}"
                                    placeholder="https://">
                                @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="tags" class="form-label fw-medium">Tags</label>
                                <input type="text" class="form-control @error('tags') is-invalid @enderror" id="tags"
                                    name="tags"
                                    value="{{ old('tags', implode(', ', $client->tagList())) }}"
                                    placeholder="vip, enterprise, monthly (comma-separated)">
                                @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label fw-medium">Address</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address"
                                    name="address" value="{{ old('address', $client->address) }}">
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label fw-medium">City</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" id="city"
                                    name="city" value="{{ old('city', $client->city) }}">
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label fw-medium">Country</label>
                                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country"
                                    name="country" value="{{ old('country', $client->country) }}">
                                @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label fw-medium">Profile notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes"
                                    rows="3" placeholder="High-level summary about this client">{{ old('notes', $client->notes) }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                {{ $client->exists ? 'Update Client' : 'Create Client' }}
                            </button>
                            <a href="{{ $client->exists ? route('clients.show', $client) : route('clients.index') }}"
                               class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
