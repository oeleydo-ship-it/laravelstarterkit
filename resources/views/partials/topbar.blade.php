{{-- Topbar --}}
<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        {{-- Mobile sidebar toggle --}}
        <button class="btn btn-sm btn-outline-secondary d-lg-none"
            onclick="document.getElementById('sidebar').classList.toggle('show')">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>
        <h1 class="page-title mb-0">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="d-flex align-items-center gap-3">
        @php $tenant = currentTenant(); @endphp
        @if($tenant && $tenant->plan)
            <span class="badge-plan">{{ $tenant->plan->name }} Plan</span>
        @endif

        {{-- User Dropdown --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" type="button"
                data-bs-toggle="dropdown">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;font-size:0.8rem;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <span class="d-none d-md-inline">{{ auth()->user()->name ?? 'User' }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email ?? '' }}</span></li>
                @if($tenant)
                    <li><span class="dropdown-item-text text-muted small">{{ $tenant->name }} ·
                            {{ ucfirst(auth()->user()->role) }}</span></li>
                @endif
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="{{ route('profile.show') }}">My Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('settings.index') }}">Settings</a></li>
                @if(auth()->user()->is_superadmin)
                    <li><a class="dropdown-item text-danger fw-medium" href="{{ route('superadmin.dashboard') }}">⚡
                            Superadmin Panel</a></li>
                @endif
                <li>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </li>
            </ul>
        </div>
    </div>
</header>