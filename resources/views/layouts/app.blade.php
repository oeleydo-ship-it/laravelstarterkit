<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SaaS Kit') }} — @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- App CSS (local Bootstrap via Vite) -->
    <script>
        window.ChatRealtime = @json(\App\Support\RealtimeConfig::forBrowser());
    </script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        :root {
            --sidebar-width: 260px;
            --topbar-height: 60px;
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --sidebar-bg: #1e1b4b;
            --sidebar-hover: #312e81;
            --body-bg: #f1f5f9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--body-bg);
        }

        /* ─── Sidebar ─── */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            z-index: 1040;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar .brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar .brand svg {
            width: 28px;
            height: 28px;
        }

        .sidebar .nav-section {
            padding: 0.75rem 0;
        }

        .sidebar .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255, 255, 255, 0.4);
        }

        .sidebar .nav-link {
            padding: 0.6rem 1.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.15s ease;
            border-left: 3px solid transparent;
        }

        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: var(--sidebar-hover);
            color: #fff;
            border-left-color: #818cf8;
            font-weight: 500;
        }

        .sidebar .nav-link svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* ─── Topbar ─── */
        .topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 1030;
        }

        .topbar .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }

        /* ─── Main Content ─── */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--topbar-height);
            padding: 1.5rem;
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ─── Cards ─── */
        .stat-card {
            background: #fff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ─── Badges ─── */
        .badge-plan {
            background: linear-gradient(135deg, #818cf8, #6366f1);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
        }

        /* ─── Table Styles ─── */
        .table-card {
            background: #fff;
            border-radius: 12px;
            border: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .table-card .table {
            margin: 0;
        }

        .table-card .table th {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            padding: 0.75rem 1rem;
        }

        .table-card .table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        /* ─── Buttons ─── */
        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        /* ─── Mobile Responsive ─── */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .topbar {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* ─── Alerts ─── */
        .alert {
            border: none;
            border-radius: 10px;
            font-size: 0.875rem;
        }
    </style>

    @stack('styles')
</head>

<body>
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Topbar -->
    @include('partials.topbar')

    <!-- Main Content -->
    <div class="main-content">
        @include('partials.alerts')
        @yield('content')
    </div>

    @stack('scripts')
</body>

</html>