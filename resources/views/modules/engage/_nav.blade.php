@php
    $links = [
        ['Dashboard', 'engage.dashboard', request()->routeIs('engage.dashboard')],
        ['Campaigns', 'engage.campaigns.index', request()->routeIs('engage.campaigns.*')],
        ['Leads', 'engage.leads.index', request()->routeIs('engage.leads.*')],
        ['Install', 'engage.install', request()->routeIs('engage.install')],
        ['Settings', 'engage.settings', request()->routeIs('engage.settings')],
    ];
@endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach($links as [$label, $route, $active])
        <a href="{{ route($route) }}"
           class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
    @endforeach
</div>
