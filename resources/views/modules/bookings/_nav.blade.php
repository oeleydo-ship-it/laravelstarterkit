@php
    $links = [
        ['Dashboard', 'bookings.dashboard', request()->routeIs('bookings.dashboard')],
        ['Services', 'bookings.services.index', request()->routeIs('bookings.services.*')],
        ['Availability', 'bookings.availability.edit', request()->routeIs('bookings.availability.*')],
        ['Appointments', 'bookings.appointments.index', request()->routeIs('bookings.appointments.*')],
        ['Install', 'bookings.install', request()->routeIs('bookings.install')],
        ['Settings', 'bookings.settings', request()->routeIs('bookings.settings')],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach($links as [$label, $route, $active])
        <a href="{{ route($route) }}"
           class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
    @endforeach
</div>
