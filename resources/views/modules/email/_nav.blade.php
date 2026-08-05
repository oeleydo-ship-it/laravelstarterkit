{{-- Email Marketing sub-navigation --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('email.dashboard') }}"
       class="btn btn-sm {{ request()->routeIs('email.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }}">Overview</a>
    <a href="{{ route('email.campaigns.index') }}"
       class="btn btn-sm {{ request()->routeIs('email.campaigns.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Campaigns</a>
    <a href="{{ route('email.lists.index') }}"
       class="btn btn-sm {{ request()->routeIs('email.lists.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Lists</a>
    <a href="{{ route('email.subscribers.index') }}"
       class="btn btn-sm {{ request()->routeIs('email.subscribers.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Subscribers</a>
    <a href="{{ route('email.templates.index') }}"
       class="btn btn-sm {{ request()->routeIs('email.templates.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Templates</a>
    <a href="{{ route('email.reports.index') }}"
       class="btn btn-sm {{ request()->routeIs('email.reports.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Reports</a>
@if(auth()->user()->hasPrivilege(\App\Support\Privileges::EMAIL_MANAGE) || auth()->user()->isOwnerOrAdmin())
        <a href="{{ route('email.settings.index') }}"
           class="btn btn-sm {{ request()->routeIs('email.settings.*') ? 'btn-primary' : 'btn-outline-secondary' }}">Settings</a>
    @endif
</div>
