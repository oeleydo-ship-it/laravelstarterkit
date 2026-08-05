@extends('layouts.app')

@section('title', 'Billing Status')

@section('content')
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Subscription Status</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small d-block">Current Plan</label>
                                <strong class="fs-5">{{ $currentPlan->name ?? 'Free' }}</strong>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small d-block">Status</label>
                                @if($subscription && $subscription->active())
                                    <span class="badge bg-success">Active</span>
                                @elseif($subscription && $subscription->onGracePeriod())
                                    <span class="badge bg-warning text-dark">Cancelling</span>
                                @else
                                    <span class="badge bg-secondary">No active subscription</span>
                                @endif
                            </div>
                        </div>

                        @if($subscription)
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="text-muted small d-block">Next Billing Date</label>
                                    <strong>{{ $subscription->asStripeSubscription()->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') : 'N/A' }}</strong>
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr>

                    <div class="d-flex gap-2">
                        <a href="{{ route('billing.plans') }}" class="btn btn-outline-primary">Change Plan</a>

                        @if($subscription && $subscription->active())
                            <a href="{{ route('billing.portal') }}" class="btn btn-outline-secondary">Manage in Stripe</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3">Plan Limits</h6>
                    @if($currentPlan)
                        @php $limits = $currentPlan->limits ?? []; @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Users</span>
                                <span>{{ $tenant->activeUserCount() }} /
                                    {{ ($limits['max_users'] ?? 0) == -1 ? '∞' : $limits['max_users'] ?? 0 }}</span>
                            </div>
                            @if(($limits['max_users'] ?? 0) > 0)
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-primary"
                                        style="width:{{ min(100, ($tenant->activeUserCount() / $limits['max_users']) * 100) }}%">
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Modules</span>
                                <span>{{ $tenant->tenantModules()->where('enabled', true)->count() }} /
                                    {{ ($limits['max_modules'] ?? 0) == -1 ? '∞' : $limits['max_modules'] ?? 0 }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-muted small">No plan selected.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection