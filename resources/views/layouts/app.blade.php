<!doctype html>
<html lang="id" data-bs-theme="light" data-lte-color-mode="off" style="color-scheme: light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'VHS Fuel Monitoring Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@auth layout-fixed sidebar-expand-lg bg-body-tertiary @else auth-page @endauth">
@auth
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" data-lte-toggle="sidebar" type="button" aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center gap-2">
                    <li class="nav-item text-end d-none d-sm-block">
                        <div class="fw-semibold">{{ auth()->user()->name }}</div>
                        <small class="text-secondary">{{ auth()->user()->roleLabel() }}</small>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Logout">
                                <i class="bi bi-box-arrow-right"></i>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="light">
            <div class="sidebar-brand">
                <a href="{{ route('dashboard') }}" class="brand-link">
                    <img src="{{ asset('images/logo-indra-angkola.png') }}" alt="Logo PT Indra Angkola Energy" class="brand-image sidebar-brand-logo">
                    <span class="brand-text sidebar-brand-copy">
                        <strong>Indra Angkola</strong>
                        <small>Fuel Monitoring</small>
                    </span>
                </a>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link @if(request()->routeIs('dashboard')) active @endif">
                                <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-header">Operation</li>
                        <li class="nav-item">
                            <a href="{{ route('receivings.index') }}" class="nav-link @if(request()->routeIs('receivings.*')) active @endif">
                                <i class="nav-icon bi bi-box-arrow-in-down"></i><p>Fuel Receiving</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('fuel-orders.index') }}" class="nav-link @if(request()->routeIs('fuel-orders.*')) active @endif">
                                <i class="nav-icon bi bi-file-earmark-text"></i><p>Fuel Order</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('dispensings.index') }}" class="nav-link @if(request()->routeIs('dispensings.*')) active @endif">
                                <i class="nav-icon bi bi-fuel-pump"></i><p>Fuel Dispensing</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('basts.index') }}" class="nav-link @if(request()->routeIs('basts.*')) active @endif">
                                <i class="nav-icon bi bi-clipboard2-check"></i><p>BAST</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reconciliations.index') }}" class="nav-link @if(request()->routeIs('reconciliations.*')) active @endif">
                                <i class="nav-icon bi bi-calculator"></i><p>Daily Reconciliation</p>
                            </a>
                        </li>
                        <li class="nav-header">Monitoring</li>
                        <li class="nav-item">
                            <a href="{{ route('inventory.current-stock') }}" class="nav-link @if(request()->routeIs('inventory.current-stock')) active @endif">
                                <i class="nav-icon bi bi-database-check"></i><p>Current Stock</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('inventory.movements') }}" class="nav-link @if(request()->routeIs('inventory.movements')) active @endif">
                                <i class="nav-icon bi bi-list-columns-reverse"></i><p>Stock Ledger</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('units.index') }}" class="nav-link @if(request()->routeIs('units.*')) active @endif">
                                <i class="nav-icon bi bi-truck"></i><p>Unit Monitoring</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.index') }}" class="nav-link @if(request()->routeIs('reports.*')) active @endif">
                                <i class="nav-icon bi bi-file-earmark-spreadsheet"></i><p>Reports</p>
                            </a>
                        </li>
                        @if(auth()->user()->hasRole(['super_admin', 'vhs_admin', 'supervisor']))
                            <li class="nav-item">
                                <a href="{{ route('outstanding-invoices.index') }}" class="nav-link @if(request()->routeIs('outstanding-invoices.*')) active @endif">
                                    <i class="nav-icon bi bi-receipt-cutoff"></i><p>Outstanding Invoice</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-header">Control</li>
                        @if(auth()->user()->hasRole(['super_admin', 'vhs_admin']))
                            <li class="nav-item">
                                <a href="{{ route('master.index') }}" class="nav-link @if(request()->routeIs('master.*')) active @endif">
                                    <i class="nav-icon bi bi-sliders"></i><p>Master Data</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('audit-logs.index') }}" class="nav-link @if(request()->routeIs('audit-logs.*')) active @endif">
                                <i class="nav-icon bi bi-shield-check"></i><p>Audit Trail</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        <main class="app-main" id="main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <h1 class="page-title mb-0">{{ $pageTitle ?? 'Dashboard' }}</h1>
                        @yield('actions')
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    @include('partials.alerts')
                    @yield('content')
                </div>
            </div>
        </main>
        <footer class="app-footer">
            <small>Copyright &copy; {{ now()->year }} PT Indra Angkola Energy. All rights reserved.</small>
        </footer>
    </div>
@else
    <main class="auth-shell" id="main">
        @yield('content')
    </main>
@endauth
@stack('scripts')
</body>
</html>
