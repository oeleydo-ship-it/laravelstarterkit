@extends('layouts.app')

@section('title', $form->exists ? 'Edit form' : 'New form')

@section('content')
    @include('modules.forms._nav')

    @php
        $fields = old('fields', $form->fields ?? []);
        if (! is_array($fields)) {
            $fields = [];
        }
        $fields = array_values($fields);
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">{{ $form->exists ? 'Edit form' : 'Customize form' }}</h4>
            @if(!empty($templateLabel))
                <p class="text-muted small mb-0">Template: <strong>{{ $templateLabel }}</strong></p>
            @endif
        </div>
        <div class="d-flex gap-2">
            @if(! $form->exists)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('forms.forms.create') }}">Change template</a>
            @endif
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('forms.forms.index') }}">Back</a>
        </div>
    </div>

    <form method="POST"
          action="{{ $form->exists ? route('forms.forms.update', $form) : route('forms.forms.store') }}"
          class="table-card">
        @csrf
        @if($form->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input class="form-control" name="name" value="{{ old('name', $form->name) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select class="form-select" name="type" required>
                    @foreach(App\Models\Form::types() as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $form->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" required>
                    @foreach(App\Models\Form::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $form->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <hr class="my-4">
        <h6 class="fw-bold">Fields</h6>
        <div id="fields" class="mb-3">
            @forelse($fields as $i => $field)
                <div class="row g-2 align-items-end field-row mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Key</label>
                        <input class="form-control" name="fields[{{ $i }}][key]" value="{{ $field['key'] ?? '' }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Label</label>
                        <input class="form-control" name="fields[{{ $i }}][label]" value="{{ $field['label'] ?? '' }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="fields[{{ $i }}][type]">
                            @foreach(['text','email','textarea','select','rating','nps'] as $type)
                                <option value="{{ $type }}" @selected(($field['type'] ?? '') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Options (select)</label>
                        <input class="form-control" name="fields[{{ $i }}][options_text]"
                               value="{{ isset($field['options']) ? implode(', ', (array) $field['options']) : '' }}"
                               placeholder="A, B, C">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="fields[{{ $i }}][required]" value="1"
                                   id="req{{ $i }}" @checked(!empty($field['required']))>
                            <label class="form-check-label" for="req{{ $i }}">Req</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field">×</button>
                    </div>
                </div>
            @empty
                <p class="text-muted small">No fields yet — add one below.</p>
            @endforelse
        </div>
        <button type="button" id="add-field" class="btn btn-sm btn-outline-secondary">Add field</button>

        <div class="mt-4">
            <label class="form-label">Thank-you message</label>
            <textarea class="form-control" name="thank_you" rows="2">{{ old('thank_you', $form->thank_you) }}</textarea>
        </div>

        <div class="d-flex justify-content-between mt-4">
            @if($form->exists)
                <button form="delete-form" type="submit" class="btn btn-outline-danger"
                        onclick="return confirm('Delete this form?')">Delete</button>
            @else
                <span></span>
            @endif
            <button class="btn btn-primary">Save form</button>
        </div>
    </form>

    @if($form->exists)
        <form id="delete-form" method="POST" action="{{ route('forms.forms.destroy', $form) }}" class="d-none">
            @csrf
            @method('DELETE')
        </form>
    @endif
@endsection

@push('scripts')
<script>
(function () {
    const wrap = document.getElementById('fields');
    let n = wrap.querySelectorAll('.field-row').length;

    document.getElementById('add-field')?.addEventListener('click', () => {
        wrap.insertAdjacentHTML('beforeend', `
            <div class="row g-2 align-items-end field-row mb-2">
                <div class="col-md-3"><label class="form-label">Key</label><input class="form-control" name="fields[${n}][key]" required></div>
                <div class="col-md-3"><label class="form-label">Label</label><input class="form-control" name="fields[${n}][label]" required></div>
                <div class="col-md-3"><label class="form-label">Type</label>
                    <select class="form-select" name="fields[${n}][type]">
                        <option value="text">text</option><option value="email">email</option>
                        <option value="textarea">textarea</option><option value="select">select</option>
                        <option value="rating">rating</option><option value="nps">nps</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Options (select)</label><input class="form-control" name="fields[${n}][options_text]" placeholder="A, B, C"></div>
                <div class="col-md-1">
                    <div class="form-check mb-2"><input type="checkbox" class="form-check-input" name="fields[${n}][required]" value="1" id="req${n}"><label class="form-check-label" for="req${n}">Req</label></div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-field">×</button>
                </div>
            </div>`);
        n += 1;
    });

    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('.field-row')?.remove();
        }
    });
})();
</script>
@endpush
