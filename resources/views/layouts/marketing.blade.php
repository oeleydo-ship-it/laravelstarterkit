<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name', 'SaaS Kit') }}</title>
    <meta name="description" content="One connected platform for live chat, email, forms, reviews, bookings, social proof, engagement, and content.">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="marketing-page">
@php $brand = config('app.name', 'SaaS Kit'); @endphp
<header class="site-header"><div class="container site-header__inner"><a href="{{ route('landing') }}" class="site-brand"><span>◆</span>{{ $brand }}</a><nav><a href="{{ route('features') }}">Features</a><a href="{{ route('pricing') }}">Pricing</a></nav><div class="header-actions">@auth<a href="{{ route('dashboard') }}" class="button button--nav">Dashboard</a>@else<a href="{{ route('login') }}" class="login-link">Log in</a><a href="{{ route('register') }}" class="button button--nav">Get started <span>→</span></a>@endauth</div></div></header>
@yield('content')
<footer class="site-footer"><div class="container"><div class="footer-main"><a href="{{ route('landing') }}" class="site-brand site-brand--footer"><span>◆</span>{{ $brand }}</a><p>One workspace for every customer touchpoint.</p><nav><a href="{{ route('features') }}">Features</a><a href="{{ route('pricing') }}">Pricing</a><a href="{{ route('login') }}">Log in</a></nav></div><div class="footer-bottom"><span>© {{ date('Y') }} {{ $brand }}</span><span>Built for customer-focused teams.</span></div></div></footer>
</body></html>
