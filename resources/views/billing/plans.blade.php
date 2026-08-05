@extends('layouts.app')

@section('title', 'Choose Plan')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold">Choose Your Plan</h4>
        <p class="text-muted">Select the plan that best fits your team's needs.</p>
    </div>

    <div class="row g-4">
        @foreach($plans as $plan)
            <div class="col-md-4">
                <div
                    class="card stat-card h-100 {{ $currentPlan && $currentPlan->id === $plan->id ? 'border-primary border-2' : '' }}">
                    <div class="card-body p-4 d-flex flex-column">
                        @if($currentPlan && $currentPlan->id === $plan->id)
                            <span class="badge bg-primary mb-2 align-self-start">Current Plan</span>
                        @endif

                        <h5 class="fw-bold">{{ $plan->name }}</h5>

                        <div class="mb-3">
                            <span class="fs-3 fw-bold">${{ number_format($plan->price_monthly, 0) }}</span>
                            <span class="text-muted">/month</span>
                        </div>

                        @if($plan->price_yearly > 0)
                            <p class="small text-muted mb-3">
                                or ${{ number_format($plan->price_yearly, 0) }}/year
                                @if($plan->monthlySavingsPercent() > 0)
                                    <span class="text-success fw-medium">(Save {{ $plan->monthlySavingsPercent() }}%)</span>
                                @endif
                            </p>
                        @endif

                        <ul class="list-unstyled mb-4 flex-grow-1">
                            @php $limits = $plan->limits ?? []; @endphp
                            <li class="mb-2 d-flex align-items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ ($limits['max_users'] ?? 0) == -1 ? 'Unlimited' : ($limits['max_users'] ?? 0) }}
                                    Users</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ ($limits['max_modules'] ?? 0) == -1 ? 'Unlimited' : ($limits['max_modules'] ?? 0) }}
                                    Modules</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>{{ ($limits['storage_limit'] ?? 0) >= 1024 ? number_format(($limits['storage_limit'] ?? 0) / 1024, 0) . ' GB' : ($limits['storage_limit'] ?? 0) . ' MB' }}
                                    Storage</span>
                            </li>
                        </ul>

                        @if(!($currentPlan && $currentPlan->id === $plan->id))
                            @if($plan->isFree())
                                <form action="{{ route('billing.checkout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan->key }}">
                                    <input type="hidden" name="interval" value="monthly">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        {{ $currentPlan ? 'Downgrade' : 'Get Started' }}
                                    </button>
                                </form>
                            @else
                                <div class="d-grid gap-2">
                                    <form action="{{ route('billing.checkout') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="plan" value="{{ $plan->key }}">
                                        <input type="hidden" name="interval" value="monthly">
                                        <button type="submit" class="btn btn-primary w-100">Monthly —
                                            ${{ number_format($plan->price_monthly, 0) }}/mo</button>
                                    </form>
                                    @if($plan->price_yearly > 0)
                                        <form action="{{ route('billing.checkout') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ $plan->key }}">
                                            <input type="hidden" name="interval" value="yearly">
                                            <button type="submit" class="btn btn-outline-primary w-100">Yearly —
                                                ${{ number_format($plan->price_yearly / 12, 0) }}/mo</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        @else
                            <a href="{{ route('billing.portal') }}" class="btn btn-outline-secondary w-100">Manage Subscription</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection