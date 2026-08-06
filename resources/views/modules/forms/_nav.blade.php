<nav class="mb-4 d-flex gap-3 border-bottom pb-2">
    <a href="{{ route('forms.dashboard') }}" class="{{ request()->routeIs('forms.dashboard') ? 'fw-bold' : '' }}">Overview</a>
    <a href="{{ route('forms.forms.index') }}" class="{{ request()->routeIs('forms.forms.*') ? 'fw-bold' : '' }}">Forms</a>
    <a href="{{ route('forms.submissions.index') }}" class="{{ request()->routeIs('forms.submissions.*') ? 'fw-bold' : '' }}">Submissions</a>
    <a href="{{ route('forms.install') }}">Install</a><a href="{{ route('forms.settings') }}">Settings</a>
</nav>
