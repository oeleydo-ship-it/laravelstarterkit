<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SaaS Kit') }} — Build Your SaaS Faster</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="landing-page">
    @php $brand = config('app.name', 'SaaS Kit'); @endphp

    <nav class="landing-nav">
        <div class="container landing-nav__inner">
            <a class="brand-mark" href="/">
                <span class="brand-mark__glyph" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                    </svg>
                </span>
                {{ $brand }}
            </a>
            <div class="landing-nav__links">
                <a href="{{ route('pricing') }}">Pricing</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="landing-nav__cta">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}" class="landing-nav__cta">Get Started</a>
                @endauth
            </div>
        </div>
    </nav>

    <section class="landing-hero">
        <div class="container">
            <div class="landing-hero__grid">
                <div class="landing-hero__copy">
                    <p class="landing-hero__brand">
                        {{ $brand }}
                        <span>built to ship.</span>
                    </p>
                    <h1 class="landing-hero__headline">Launch a multi-tenant SaaS without months of boilerplate.</h1>
                    <p class="landing-hero__lede">
                        Billing, teams, modules, and admin tools — wired up so you can focus on the product.
                    </p>
                    <div class="landing-hero__actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-landing btn-landing--primary">Open Dashboard</a>
                        @else
                            <a href="{{ route('register') }}" class="btn-landing btn-landing--primary">Start Free</a>
                        @endauth
                        <a href="{{ route('pricing') }}" class="btn-landing btn-landing--ghost">View Pricing</a>
                    </div>
                </div>

                <div class="hero-stage" aria-hidden="true">
                    <div class="hero-stage__glow"></div>
                    <div class="hero-stage__frame">
                        <div class="hero-stage__chrome">
                            <span class="hero-stage__dot"></span>
                            <span class="hero-stage__dot"></span>
                            <span class="hero-stage__dot"></span>
                        </div>
                        <div class="hero-stage__body">
                            <div class="hero-stage__rail">
                                <span></span><span></span><span></span><span></span><span></span>
                            </div>
                            <div class="hero-stage__main">
                                <div class="hero-stage__bar"></div>
                                <div class="hero-stage__rows">
                                    <div class="hero-stage__row"></div>
                                    <div class="hero-stage__row"></div>
                                    <div class="hero-stage__row"></div>
                                </div>
                                <div class="hero-stage__stats">
                                    <div class="hero-stage__stat"></div>
                                    <div class="hero-stage__stat"></div>
                                    <div class="hero-stage__stat"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="landing-features">
        <div class="container">
            <div class="landing-features__intro">
                <h2>Everything you need to run tenants</h2>
                <p>Core SaaS primitives, ready to extend with your own modules.</p>
            </div>

            @php
                $features = [
                    ['title' => 'Multi-Tenant', 'desc' => 'Workspace isolation from day one. Each team gets their own scoped data.'],
                    ['title' => 'Stripe Billing', 'desc' => 'Subscriptions, plan upgrades, downgrades, and billing portal built-in.'],
                    ['title' => 'Team & RBAC', 'desc' => 'Invite members, assign roles (Owner, Admin, Member), manage permissions.'],
                    ['title' => 'Modular Architecture', 'desc' => 'Enable or disable feature modules per tenant. Pay for what you use.'],
                    ['title' => 'Settings System', 'desc' => 'Tenant-scoped and global settings with logo upload and timezone support.'],
                    ['title' => 'Admin Dashboard', 'desc' => 'Analytics widgets, activity logs, and a polished Bootstrap 5 UI.'],
                ];
            @endphp

            <div class="feature-list">
                @foreach($features as $f)
                    <div class="feature-list__item">
                        <h3 class="feature-list__title">{{ $f['title'] }}</h3>
                        <p class="feature-list__desc">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <footer class="landing-footer">
        <div class="container">
            &copy; {{ date('Y') }} {{ $brand }}. All rights reserved.
        </div>
    </footer>
</body>

</html>
