@extends('layouts.app')

@section('title', 'Import Subscribers')

@section('content')
    @include('modules.email._nav')

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2">Import CSV</h5>
                    <p class="text-muted small mb-4">CSV should include an <code>email</code> column. Optional: <code>first_name</code>, <code>last_name</code>.</p>

                    <form method="POST" action="{{ route('email.subscribers.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="list_id">Target list *</label>
                            <select name="list_id" id="list_id" class="form-select @error('list_id') is-invalid @enderror" required>
                                <option value="">Select a list</option>
                                @foreach($lists as $list)
                                    <option value="{{ $list->id }}" @selected(old('list_id') == $list->id)>{{ $list->name }}</option>
                                @endforeach
                            </select>
                            @error('list_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium" for="csv">CSV file *</label>
                            <input type="file" name="csv" id="csv" class="form-control @error('csv') is-invalid @enderror" accept=".csv,text/csv" required>
                            @error('csv') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="resubscribe" value="1" id="resubscribe" @checked(old('resubscribe'))>
                            <label class="form-check-label" for="resubscribe">
                                Re-subscribe previously unsubscribed contacts
                            </label>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary">Import</button>
                            <a href="{{ route('email.subscribers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
