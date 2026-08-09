@extends('layouts.marketing')
@section('title', 'Simple pricing for every stage')
@section('content')
<main>
    <section class="page-hero"><div class="container"><span class="eyebrow eyebrow--dark">SIMPLE, FLEXIBLE PRICING</span><h1>Start lean. Grow without limits.</h1><p>Every plan gives you the foundation to capture, connect, and convert. Upgrade whenever your momentum demands it.</p></div></section>
    <section class="pricing-section"><div class="container"><div class="pricing-grid">
        @forelse($plans as $plan)
            @php $featured = $plan->key === 'pro'; $limits = $plan->limits ?? []; @endphp
            <article class="price-card {{ $featured ? 'price-card--featured' : '' }}">
                @if($featured)<span class="popular-badge">BEST FOR GROWING TEAMS</span>@endif
                <span class="plan-label">{{ strtoupper($plan->name) }}</span>
                <div class="plan-price"><sup>$</sup>{{ number_format($plan->price_monthly, 0) }}<small>/ month</small></div>
                <p>{{ $featured ? 'The complete growth stack for teams ready to scale.' : ($plan->price_monthly > 0 ? 'More capacity for an established customer operation.' : 'Everything you need to prove your growth engine.') }}</p>
                <a href="{{ route('register') }}" class="button {{ $featured ? 'button--primary' : 'button--outline' }}">{{ $plan->price_monthly > 0 ? 'Choose '.$plan->name : 'Start free' }} →</a>
                <ul><li><b>{{ ($limits['max_users'] ?? 0) == -1 ? 'Unlimited' : ($limits['max_users'] ?? 0) }}</b> team members</li><li><b>{{ ($limits['max_modules'] ?? 0) == -1 ? 'All' : ($limits['max_modules'] ?? 0) }}</b> growth modules</li><li><b>{{ ($limits['storage_limit'] ?? 0) >= 1024 ? number_format(($limits['storage_limit'] ?? 0) / 1024, 0).' GB' : ($limits['storage_limit'] ?? 0).' MB' }}</b> storage</li><li>Team roles and permissions</li><li>Workspace analytics</li></ul>
                @if($plan->price_yearly > 0)<small class="yearly-note">Or ${{ number_format($plan->price_yearly, 0) }}/year @if($plan->monthlySavingsPercent() > 0) · Save {{ $plan->monthlySavingsPercent() }}%@endif</small>@endif
            </article>
        @empty
            <div class="empty-plans">Plans are being configured. Create an account and we’ll help you choose the right setup.</div>
        @endforelse
    </div><p class="pricing-footnote">All plans include secure tenant isolation, responsive widgets, and access to product updates.</p></div></section>
    <section class="faq-section"><div class="container"><div class="section-heading"><span class="eyebrow eyebrow--dark">GOOD TO KNOW</span><h2>Questions, answered.</h2></div><div class="faq-grid"><article><h3>Can I change plans later?</h3><p>Yes. Upgrade or downgrade as your team and customer volume changes.</p></article><article><h3>Do I need a developer?</h3><p>No. Each module includes guided settings and simple install snippets for your website.</p></article><article><h3>Are the modules connected?</h3><p>Yes. They share the same workspace, team, customer context, and reporting foundation.</p></article><article><h3>Is my data separated?</h3><p>Yes. Every workspace is tenant-scoped with roles and permissions built in.</p></article></div></div></section>
</main>
@endsection
