<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach([['Dashboard','reviews.dashboard'],['Reviews','reviews.index'],['Widgets','reviews.widgets.index'],['Install','reviews.install'],['Settings','reviews.settings']] as [$label,$route])
        <a href="{{ route($route) }}" class="btn btn-sm {{ request()->routeIs($route) || ($route === 'reviews.index' && request()->routeIs('reviews.approve','reviews.reject')) ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
    @endforeach
</div>
