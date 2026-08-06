@extends('layouts.app')

@section('title', 'Bookings')

@section('content')
    @include('modules.bookings._nav')

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">Bookings</h4>
            <p class="text-muted mb-0 small">Services, availability, and appointments.</p>
        </div>
        <a href="{{ $publicUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">Open public page</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Active services', $stats['services']],
            ['Upcoming', $stats['upcoming']],
            ['All appointments', $stats['total']],
        ] as [$label, $value])
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <div class="text-muted small">{{ $label }}</div>
                        <div class="fs-4 fw-bold">{{ number_format($value) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="table-card">
        <h6 class="fw-bold mb-3">Upcoming</h6>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Service</th>
                    <th>Guest</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($upcoming as $row)
                    <tr>
                        <td>{{ $row->starts_at?->timezone($site->timezone)->format('M j, g:i A') }}</td>
                        <td>{{ $row->service?->name }}</td>
                        <td>{{ $row->guest_name }} <span class="text-muted small">{{ $row->guest_email }}</span></td>
                        <td><span class="badge {{ $row->statusBadgeClass() }}">{{ $row->statusLabel() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center py-4">No upcoming appointments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
