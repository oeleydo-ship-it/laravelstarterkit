@extends('layouts.app')

@section('title', 'Services')

@section('content')
    @include('modules.bookings._nav')

    <div class="d-flex justify-content-between mb-4">
        <h4 class="fw-bold mb-0">Services</h4>
        @can('create', App\Models\BookingService::class)
            <a href="{{ route('bookings.services.create') }}" class="btn btn-primary btn-sm">+ New service</a>
        @endcan
    </div>

    <div class="table-card">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Buffer</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($services as $service)
                    <tr>
                        <td class="fw-medium">{{ $service->name }}</td>
                        <td>{{ $service->duration_minutes }} min</td>
                        <td>{{ $service->buffer_minutes }} min</td>
                        <td>{{ $service->active ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            <a href="{{ route('bookings.services.edit', $service) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No services yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $services->links() }}</div>
@endsection
