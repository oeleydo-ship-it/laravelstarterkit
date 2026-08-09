@extends('layouts.marketing')
@section('title', 'Features built for the full customer journey')
@section('content')
<main>
    <section class="page-hero page-hero--features"><div class="container"><span class="eyebrow eyebrow--dark">ONE CONNECTED PLATFORM</span><h1>Eight tools. One customer story.</h1><p>Choose the modules you need today, then switch on more as your customer journey grows.</p></div></section>
    @php $modules = config('marketing_modules'); @endphp
    <section class="section modules-section"><div class="container"><div class="module-list">@foreach($modules as $slug => $module)<article><span class="module-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><span class="module-tag">{{ $module['tag'] }}</span><h2><a href="{{ route('modules.show', $slug) }}">{{ $module['name'] }}</a></h2><p>{{ $module['intro'] }}</p></div><a href="{{ route('modules.show', $slug) }}" aria-label="Explore {{ $module['name'] }}">↗</a></article>@endforeach</div></div></section>
    <section class="workflow-section"><div class="container feature-foundation"><div><span class="eyebrow">BUILT ON A SOLID FOUNDATION</span><h2>The business essentials are already handled.</h2></div><div class="foundation-grid"><span>Multi-tenant workspaces</span><span>Stripe subscriptions</span><span>Team invitations</span><span>Role-based access</span><span>Activity logs</span><span>Global settings</span></div></div></section>
    <section class="section final-cta"><div class="container"><div class="final-cta__card"><h2>Build the stack that fits your growth.</h2><p>Start with the modules you need. Add the rest when you’re ready.</p><a href="{{ route('pricing') }}" class="button button--primary">See plans and pricing →</a></div></div></section>
</main>
@endsection
