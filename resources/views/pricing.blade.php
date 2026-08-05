<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SaaS Kit') }} — Pricing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="pricing-page">
    @php $brand = config('app.name', 'SaaS Kit'); @endphp

    <nav class="pricing-nav">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="brand-mark" href="/">
                <span class="brand-mark__glyph" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                    </svg>
                </span>
                {{ $brand }}
            </a>
            <div class="pricing-nav__links">
                <a href="/">Home</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="pricing-nav__cta">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}" class="pricing-nav__cta">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="pricing-hero">
            <h1>Simple, transparent pricing</h1>
            <p>Choose the plan that fits your team — upgrade anytime.</p>
        </div>

        <div class="row g-4 justify-content-center">
            @foreach($plans as $plan)
                @php $featured = $plan->key === 'pro'; @endphp
                <div class="col-md-4">
                    <div class="plan-pick {{ $featured ? 'plan-pick--featured' : '' }}">
                        @if($featured)
                            <span class="plan-pick__badge">Most Popular</span>
                        @endif

                        <h4 class="fw-bold mb-2">{{ $plan->name }}</h4>

                        <div class="plan-pick__price mb-1">
                            ${{ number_format($plan->price_monthly, 0) }}
                            <span>/month</span>
                        </div>

                        @if($plan->price_yearly > 0)
                            <p class="small text-muted mb-0">
                                ${{ number_format($plan->price_yearly, 0) }}/year
                                @if($plan->monthlySavingsPercent() > 0)
                                    · Save {{ $plan->monthlySavingsPercent() }}%
                                @endif
                            </p>
                        @else
                            <p class="small text-muted mb-0">Free forever</p>
                        @endif

                        <ul>
                            @php $limits = $plan->limits ?? []; @endphp
                            <li>
                                {{ ($limits['max_users'] ?? 0) == -1 ? 'Unlimited' : ($limits['max_users'] ?? 0) }}
                                Users
                            </li>
                            <li>
                                {{ ($limits['max_modules'] ?? 0) == -1 ? 'Unlimited' : ($limits['max_modules'] ?? 0) }}
                                Modules
                            </li>
                            <li>
                                {{ ($limits['storage_limit'] ?? 0) >= 1024 ? number_format(($limits['storage_limit'] ?? 0) / 1024, 0) . ' GB' : ($limits['storage_limit'] ?? 0) . ' MB' }}
                                Storage
                            </li>
                        </ul>

                        <a href="{{ route('register') }}"
                            class="btn-plan {{ $featured ? 'btn-plan--featured' : 'btn-plan--outline' }}">
                            Get Started
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>

</html>
