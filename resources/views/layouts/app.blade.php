<!DOCTYPE html>
{{--
    Main authenticated app layout. Implements Phase 6 Section 3
    (Navigation): persistent collapsible left sidebar, top bar with
    org switcher/notifications/mode toggle, and role-aware nav items
    filtered server-side (never rendered-then-hidden) so a role never
    even sees markup for a section it can't access.

    Alpine.js (already in the confirmed stack per Phase 4 Section
    "Blade + Bootstrap 5 + Alpine.js") handles: sidebar collapse state,
    dark/light mode toggle with localStorage-free, system-preference
    default (Phase 6 Section 2.5).
--}}
<html lang="en" x-data="{ sidebarCollapsed: false }" x-init="
    document.documentElement.dataset.theme =
        window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CybCademy')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
</head>
<body>
    <div class="d-flex">
        {{-- Sidebar - Phase 6 Section 3: persistent, collapsible, role-filtered --}}
        <nav class="cyb-sidebar flex-shrink-0 p-3" :class="{ collapsed: sidebarCollapsed }">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <span class="cyb-display fw-bold" style="color: #fff;" x-show="!sidebarCollapsed">CybCademy</span>
                <button class="btn btn-sm btn-link text-white" @click="sidebarCollapsed = !sidebarCollapsed"
                        aria-label="Toggle sidebar">
                    <i class="bi bi-layout-sidebar"></i>
                </button>
            </div>

            <div class="nav flex-column">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i><span x-show="!sidebarCollapsed">Dashboard</span>
                </a>
                <a href="{{ route('courses.index') }}" class="nav-link {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                    <i class="bi bi-mortarboard me-2"></i><span x-show="!sidebarCollapsed">Training</span>
                </a>

                {{-- Role-aware: this markup is simply never rendered for
                     an Employee - not hidden via CSS, per Phase 6 Section
                     3's "reduces cognitive load and avoids implying
                     access that doesn't exist." --}}
                @can('policies.view')
                    <a href="{{ route('dashboard.compliance') }}" class="nav-link {{ request()->routeIs('dashboard.compliance') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-data me-2"></i><span x-show="!sidebarCollapsed">Compliance</span>
                    </a>
                @endcan

                @can('phishing_campaigns.view')
                    <a href="{{ route('phishing.index') }}" class="nav-link {{ request()->routeIs('phishing.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-exclamation me-2"></i><span x-show="!sidebarCollapsed">Phishing Simulation</span>
                    </a>
                @endcan

                @can('policies.view')
                    <a href="{{ route('policies.index') }}" class="nav-link {{ request()->routeIs('policies.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text me-2"></i><span x-show="!sidebarCollapsed">Policies</span>
                    </a>
                @endcan

                @can('audit.view')
                    <a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check me-2"></i><span x-show="!sidebarCollapsed">Audit Centre</span>
                    </a>
                @endcan

                <a href="{{ route('incidents.create') }}" class="nav-link mt-3" style="background-color: rgba(209,67,67,0.2);">
                    <i class="bi bi-exclamation-triangle me-2"></i><span x-show="!sidebarCollapsed">Report Phishing</span>
                </a>
            </div>
        </nav>

        <div class="flex-grow-1">
            {{-- Top bar --}}
            <header class="d-flex align-items-center justify-content-between p-3 border-bottom"
                    style="background-color: var(--cyb-surface-raised); border-color: var(--cyb-border) !important;">
                <div></div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-link" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                    </button>
                    <button class="btn btn-sm btn-link"
                            @click="
                                const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                                document.documentElement.dataset.theme = next;
                            "
                            aria-label="Toggle dark mode">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Log out</button>
                    </form>
                </div>
            </header>

            <main class="p-4">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
