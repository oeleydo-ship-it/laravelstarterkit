@extends('layouts.superadmin')

@section('title', 'System Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(79,70,229,0.1);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">Total Tenants</div>
                        <div class="fs-4 fw-bold">{{ $totalTenants }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.1);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">Total Users</div>
                        <div class="fs-4 fw-bold">{{ $totalUsers }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.1);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">Active Plans</div>
                        <div class="fs-4 fw-bold">{{ $activePlans }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Recent Tenants --}}
        <div class="col-lg-6">
            <div class="table-card">
                <div class="px-4 py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold">Recent Tenants</h5>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Plan</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTenants as $tenant)
                            <tr>
                                <td class="fw-medium">{{ $tenant->name }}</td>
                                <td><span
                                        class="badge bg-primary bg-opacity-10 text-primary">{{ $tenant->plan->name ?? 'None' }}</span>
                                </td>
                                <td class="text-muted small">{{ $tenant->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No tenants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Users --}}
        <div class="col-lg-6">
            <div class="table-card">
                <div class="px-4 py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold">Recent Users</h5>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Workspace</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr>
                                <td>
                                    <div>
                                        <div class="fw-medium">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </div>
                                </td>
                                <td class="small">{{ $user->tenant->name ?? '—' }}</td>
                                <td><span class="badge bg-secondary">{{ ucfirst($user->role ?? 'superadmin') }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No users yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection