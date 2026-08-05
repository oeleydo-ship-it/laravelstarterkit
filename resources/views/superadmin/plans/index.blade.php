@extends('layouts.superadmin')

@section('title', 'Manage Plans')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Subscription Plans</h4>
        <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary">+ New Plan</a>
    </div>

    <div class="table-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Key</th>
                    <th>Monthly</th>
                    <th>Yearly</th>
                    <th>Limits</th>
                    <th>Tenants</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td class="fw-medium">{{ $plan->name }}</td>
                        <td><code>{{ $plan->key }}</code></td>
                        <td>${{ number_format($plan->price_monthly, 2) }}</td>
                        <td>${{ number_format($plan->price_yearly, 2) }}</td>
                        <td>
                            <span class="small">
                                {{ $plan->getLimit('max_users', '∞') == -1 ? '∞' : $plan->getLimit('max_users') }} users,
                                {{ $plan->getLimit('max_modules', '∞') == -1 ? '∞' : $plan->getLimit('max_modules') }} modules
                            </span>
                        </td>
                        <td><span class="badge bg-secondary">{{ $plan->tenants()->count() }}</span></td>
                        <td>
                            @if($plan->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('superadmin.plans.edit', $plan) }}"
                                    class="btn btn-sm btn-outline-primary">Edit</a>
                                @if($plan->tenants()->count() === 0)
                                    <form method="POST" action="{{ route('superadmin.plans.destroy', $plan) }}"
                                        onsubmit="return confirm('Delete this plan?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No plans yet. <a
                                href="{{ route('superadmin.plans.create') }}">Create your first plan</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection