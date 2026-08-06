@extends('layouts.app')

@section('title', 'Availability')

@section('content')
    @include('modules.bookings._nav')

    <h4 class="fw-bold mb-1">Availability</h4>
    <p class="text-muted small mb-4">Weekly hours in {{ $site->timezone }}. Add exceptions for holidays.</p>

    <form method="POST" action="{{ route('bookings.availability.update') }}" class="table-card mb-4">
        @csrf
        @method('PUT')
        <div id="rules">
            @php $existing = old('rules', $rules->map(fn ($r) => [
                'weekday' => $r->weekday,
                'start_time' => substr((string) $r->start_time, 0, 5),
                'end_time' => substr((string) $r->end_time, 0, 5),
            ])->all()); @endphp
            @forelse($existing as $i => $rule)
                <div class="row g-2 mb-2 rule-row">
                    <div class="col-md-4">
                        <select name="rules[{{ $i }}][weekday]" class="form-select">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $w => $label)
                                <option value="{{ $w }}" @selected((int)$rule['weekday'] === $w)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="time" name="rules[{{ $i }}][start_time]" class="form-control" value="{{ $rule['start_time'] }}" required>
                    </div>
                    <div class="col-md-3">
                        <input type="time" name="rules[{{ $i }}][end_time]" class="form-control" value="{{ $rule['end_time'] }}" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="this.closest('.rule-row').remove()">Remove</button>
                    </div>
                </div>
            @empty
                <div class="row g-2 mb-2 rule-row">
                    <div class="col-md-4">
                        <select name="rules[0][weekday]" class="form-select">
                            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $w => $label)
                                <option value="{{ $w }}" @selected($w === 1)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><input type="time" name="rules[0][start_time]" class="form-control" value="09:00" required></div>
                    <div class="col-md-3"><input type="time" name="rules[0][end_time]" class="form-control" value="17:00" required></div>
                    <div class="col-md-2"><button type="button" class="btn btn-outline-secondary w-100" onclick="this.closest('.rule-row').remove()">Remove</button></div>
                </div>
            @endforelse
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" id="add-rule">+ Add hours</button>
        <div><button class="btn btn-primary">Save weekly hours</button></div>
    </form>

    <div class="table-card">
        <h6 class="fw-bold mb-3">Date exceptions</h6>
        <form method="POST" action="{{ route('bookings.availability.exceptions.store') }}" class="row g-2 mb-3">
            @csrf
            <div class="col-md-3"><input type="date" name="date" class="form-control" required></div>
            <div class="col-md-2">
                <div class="form-check mt-2">
                    <input type="checkbox" name="is_closed" value="1" class="form-check-input" id="closed" checked>
                    <label for="closed" class="form-check-label">Closed</label>
                </div>
            </div>
            <div class="col-md-2"><input type="time" name="start_time" class="form-control" placeholder="Start"></div>
            <div class="col-md-2"><input type="time" name="end_time" class="form-control" placeholder="End"></div>
            <div class="col-md-3"><button class="btn btn-outline-primary w-100">Add exception</button></div>
        </form>
        <ul class="list-unstyled mb-0">
            @forelse($exceptions as $ex)
                <li class="d-flex justify-content-between border-bottom py-2">
                    <span>{{ $ex->date->format('M j, Y') }} —
                        {{ $ex->is_closed ? 'Closed' : (substr((string)$ex->start_time,0,5).'–'.substr((string)$ex->end_time,0,5)) }}
                    </span>
                    <form method="POST" action="{{ route('bookings.availability.exceptions.destroy', $ex) }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-link text-danger">Remove</button>
                    </form>
                </li>
            @empty
                <li class="text-muted small">No exceptions.</li>
            @endforelse
        </ul>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('add-rule')?.addEventListener('click', () => {
    const wrap = document.getElementById('rules');
    const i = wrap.querySelectorAll('.rule-row').length;
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 rule-row';
    div.innerHTML = `
        <div class="col-md-4">
            <select name="rules[${i}][weekday]" class="form-select">
                <option value="0">Sun</option><option value="1" selected>Mon</option><option value="2">Tue</option>
                <option value="3">Wed</option><option value="4">Thu</option><option value="5">Fri</option><option value="6">Sat</option>
            </select>
        </div>
        <div class="col-md-3"><input type="time" name="rules[${i}][start_time]" class="form-control" value="09:00" required></div>
        <div class="col-md-3"><input type="time" name="rules[${i}][end_time]" class="form-control" value="17:00" required></div>
        <div class="col-md-2"><button type="button" class="btn btn-outline-secondary w-100" onclick="this.closest('.rule-row').remove()">Remove</button></div>`;
    wrap.appendChild(div);
});
</script>
@endpush
