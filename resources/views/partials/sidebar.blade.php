{{-- Sidebar Navigation --}}
<nav class="sidebar" id="sidebar">
    <div class="brand">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </svg>
        {{ config('app.name', 'SaaS Kit') }}
    </div>

    <div class="nav-section">
        <div class="nav-section-title">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1" />
                <rect x="14" y="3" width="7" height="7" rx="1" />
                <rect x="3" y="14" width="7" height="7" rx="1" />
                <rect x="14" y="14" width="7" height="7" rx="1" />
            </svg>
            Dashboard
        </a>
    </div>

    @php $tenant = currentTenant(); @endphp

    @if($tenant)
        {{-- Module Navigation Items --}}
        @if(($tenant->isModuleEnabled('clients') && auth()->user()->canAccessModule('clients'))
            || ($tenant->isModuleEnabled('tickets') && auth()->user()->canAccessModule('tickets'))
            || ($tenant->isModuleEnabled('chat') && auth()->user()->canAccessModule('chat'))
            || ($tenant->isModuleEnabled('email') && auth()->user()->canAccessModule('email'))
            || ($tenant->isModuleEnabled('engage') && auth()->user()->canAccessModule('engage'))
            || ($tenant->isModuleEnabled('forms') && auth()->user()->canAccessModule('forms'))
            || ($tenant->isModuleEnabled('reviews') && auth()->user()->canAccessModule('reviews'))
            || ($tenant->isModuleEnabled('bookings') && auth()->user()->canAccessModule('bookings'))
            || ($tenant->isModuleEnabled('socialproof') && auth()->user()->canAccessModule('socialproof'))
            || ($tenant->isModuleEnabled('autoblog') && auth()->user()->canAccessModule('autoblog')))
            <div class="nav-section">
                <div class="nav-section-title">Modules</div>

                @if($tenant->isModuleEnabled('clients') && auth()->user()->canAccessModule('clients'))
                    <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        CRM
                    </a>
                @endif

                @if($tenant->isModuleEnabled('tickets') && auth()->user()->canAccessModule('tickets'))
                    <a href="{{ route('tickets.index') }}"
                        class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path
                                d="M15 5v2M15 11v2M15 17v2M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z" />
                        </svg>
                        Tickets
                    </a>
                @endif

                @if($tenant->isModuleEnabled('chat') && auth()->user()->canAccessModule('chat'))
                    <a href="{{ route('chat.conversations.index') }}"
                        class="nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
                        </svg>
                        Live Chat
                    </a>
                @endif

                @if($tenant->isModuleEnabled('email') && auth()->user()->canAccessModule('email'))
                    <a href="{{ route('email.dashboard') }}"
                        class="nav-link {{ request()->routeIs('email.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                        </svg>
                        Email Marketing
                    </a>
                @endif

                @if($tenant->isModuleEnabled('engage') && auth()->user()->canAccessModule('engage'))
                    <a href="{{ route('engage.dashboard') }}"
                        class="nav-link {{ request()->routeIs('engage.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        Engage
                    </a>
                @endif

                @if($tenant->isModuleEnabled('forms') && auth()->user()->canAccessModule('forms'))
                    <a href="{{ route('forms.dashboard') }}" class="nav-link {{ request()->routeIs('forms.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                        Forms
                    </a>
                @endif

                @if($tenant->isModuleEnabled('reviews') && auth()->user()->canAccessModule('reviews'))
                    <a href="{{ route('reviews.dashboard') }}" class="nav-link {{ request()->routeIs('reviews.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 20.5s-7-4.4-7-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 7 3.3c0 5.8-7 10.2-7 10.2z" />
                            <path d="m12 9 .9 1.8 2 .3-1.5 1.4.4 2-1.8-.9-1.8.9.4-2-1.5-1.4 2-.3z" />
                        </svg>
                        Reviews
                    </a>
                @endif

                @if($tenant->isModuleEnabled('bookings') && auth()->user()->canAccessModule('bookings'))
                    <a href="{{ route('bookings.dashboard') }}" class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        Bookings
                    </a>
                @endif

                @if($tenant->isModuleEnabled('socialproof') && auth()->user()->canAccessModule('socialproof'))
                    <a href="{{ route('socialproof.dashboard') }}" class="nav-link {{ request()->routeIs('socialproof.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                            <circle cx="18" cy="6" r="3" />
                        </svg>
                        Social Proof
                    </a>
                @endif
                @if($tenant->isModuleEnabled('autoblog') && auth()->user()->canAccessModule('autoblog'))
                    <a href="{{ route('autoblog.dashboard') }}" class="nav-link {{ request()->routeIs('autoblog.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5V4a2 2 0 0 1 2-2h11l3 3v14.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 19.5z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>
                        AI Autoblog
                    </a>
                @endif
            </div>
        @endif

            <div class="nav-section">
                <div class="nav-section-title">Workspace</div>

                @can('manage-team')
                    <a href="{{ route('team.index') }}" class="nav-link {{ request()->routeIs('team.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 4.354a4 4 0 1 1 0 7.292M15 21H3v-1a6 6 0 0 1 12 0v1zm0 0h6v-1a6 6 0 0 0-9-5.197" />
                        </svg>
                        Team
                    </a>
                @endcan

                @can('manage-modules')
                    <a href="{{ route('modules.index') }}"
                        class="nav-link {{ request()->routeIs('modules.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M3 9h18M9 21V9" />
                        </svg>
                        Modules
                    </a>
                @endcan

                @can('manage-billing')
                    <a href="{{ route('billing.plans') }}"
                        class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                        Billing
                    </a>
                @endcan

                @can('manage-settings')
                    <a href="{{ route('settings.index') }}"
                        class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3" />
                            <path
                                d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                        </svg>
                        Settings
                    </a>
                @endcan
            </div>
    @endif
</nav>
