@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        {{-- Total Users Card --}}
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary bg-opacity-10">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">Total Users</div>
                        <div class="fs-4 fw-bold">{{ $totalUsers }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Modules Card --}}
        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.1);">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M9 21V9" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">Active Modules</div>
                        <div class="fs-4 fw-bold">{{ $activeModules }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Plan Card --}}
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
                        <div class="text-muted small">Current Plan</div>
                        <div class="fs-4 fw-bold">{{ currentTenant()->plan->name ?? 'Free' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="table-card">
        <div class="card-body p-0">
            <div class="px-4 py-3 border-bottom">
                <h5 class="mb-0 fw-semibold">Recent Activity</h5>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivity as $log)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px;font-size:0.75rem;">
                                        {{ strtoupper(substr($log->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    {{ $log->user->name ?? 'System' }}
                                </div>
                            </td>
                            <td>{{ $log->description }}</td>
                            <td class="text-muted small">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No activity yet. Start using your workspace!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection