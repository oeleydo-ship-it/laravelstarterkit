@extends('layouts.app')

@section('title', 'Appointments')

@section('content')
    @include('modules.bookings._nav')

    <h4 class="fw-bold mb-3">Appointments</h4>

    <form method="GET" class="mb-3 d-flex gap-2">
        <input name="q" class="form-control form-control-sm" style="max-width:280px" value="{{ request('q') }}" placeholder="Search guest name or email">
        <select name="status" class="form-select form-select-sm" style="max-width:180px" onchange="this.form.submit()">
            <option value="">All statuses</option>
            @foreach(App\Models\BookingAppointment::statuses() as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-outline-secondary">Search</button>
    </form>

    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Service</th>
                    <th>Guest</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $row)
                    <tr>
                        <td>{{ $row->starts_at?->format('M j, Y g:i A') }}</td>
                        <td>{{ $row->service?->name }}</td>
                        <td>
                            <div class="fw-medium">{{ $row->guest_name }}</div>
                            <div class="small text-muted">{{ $row->guest_email }}</div>
                            @if($row->guest_phone)
                                <div class="small text-muted">{{ $row->guest_phone }}</div>
                            @endif
                            @if($row->notes)
                                <div class="small text-muted mt-1">{{ $row->notes }}</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('bookings.appointments.show', $row) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @can('update', $row)
                                <form method="POST" action="{{ route('bookings.appointments.status', $row) }}" class="d-inline">
                                    @csrf @method('PUT')
                                    <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto" onchange="this.form.submit()">
                                        @foreach(App\Models\BookingAppointment::statuses() as $value => $label)
                                            <option value="{{ $value }}" @selected($row->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No appointments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $appointments->links() }}</div>
@endsection
