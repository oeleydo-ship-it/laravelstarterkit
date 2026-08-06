<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach([
        ['Dashboard', 'socialproof.dashboard'],
        ['Notifications', 'socialproof.events.index'],
        ['Install', 'socialproof.install'],
        ['Settings', 'socialproof.settings'],
    ] as [$label, $route])
        <a href="{{ route($route) }}"
           class="btn btn-sm {{ request()->routeIs($route) || ($route === 'socialproof.events.index' && request()->routeIs('socialproof.events.*')) ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
