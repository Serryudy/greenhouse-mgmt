<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Verdantia</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name ?? 'U'))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    $navItems = [
        ['route' => 'dashboard',        'pattern' => 'dashboard',     'label' => 'Dashboard',     'icon' => 'grid'],
        ['route' => 'greenhouses.index','pattern' => 'greenhouses.*',  'label' => 'Greenhouses',   'icon' => 'home'],
        ['route' => 'devices.index',    'pattern' => 'devices.*',      'label' => 'Devices',       'icon' => 'cpu'],
        ['route' => 'thresholds.index', 'pattern' => 'thresholds.*',   'label' => 'Thresholds',    'icon' => 'sliders'],
        ['route' => 'alerts.index',     'pattern' => 'alerts.*',       'label' => 'Alerts',        'icon' => 'bell', 'badge' => $sidebarAlertCount],
        ['route' => 'control.index',    'pattern' => 'control.*',      'label' => 'Control Panel', 'icon' => 'gauge'],
        ['route' => 'schedules.index',  'pattern' => 'schedules.*',    'label' => 'Schedules',     'icon' => 'calendar'],
        ['route' => 'reports.index',    'pattern' => 'reports.*',      'label' => 'Reports',       'icon' => 'chart'],
    ];
@endphp
<div class="app-shell">
    {{-- ============ SIDEBAR ============ --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-logo">@include('partials.icon', ['name' => 'leaf', 'size' => 22])</span>
            <div>
                <div class="brand-name">Verdantia</div>
                <div class="brand-sub">Greenhouse OS</div>
            </div>
        </div>

        <div class="sidebar-section">Monitoring</div>
        <nav class="sidebar-nav">
            @foreach ($navItems as $item)
                <a href="{{ route($item['route']) }}"
                   class="nav-link-item {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                    <span class="nav-icon">@include('partials.icon', ['name' => $item['icon'], 'size' => 18])</span>
                    <span>{{ $item['label'] }}</span>
                    @if (!empty($item['badge']) && $item['badge'] > 0)
                        <span class="nav-badge">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="sidebar-user">
            <span class="avatar">{{ strtoupper($initials) }}</span>
            <div>
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-meta">
                    {{ $user->isAdmin() ? 'Administrator' : 'Site Operator' }} ·
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                </div>
            </div>
        </div>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </aside>

    {{-- ============ MAIN ============ --}}
    <main class="app-main">
        <header class="page-header">
            <div>
                <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                <div class="page-subtitle">@yield('subtitle', 'Live overview · last synced just now')</div>
            </div>
            <div class="header-tools">
                <div class="dropdown">
                    <button class="gh-selector dropdown-toggle" data-bs-toggle="dropdown" type="button">
                        @include('partials.icon', ['name' => 'home', 'size' => 15])
                        {{ isset($currentGreenhouse) && $currentGreenhouse ? $currentGreenhouse->name : 'All greenhouses' }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['greenhouse' => null]) }}">All greenhouses</a></li>
                        @foreach ($sidebarGreenhouses as $g)
                            <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['greenhouse' => $g->id]) }}">{{ $g->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <span class="status-online-pill">
                    <span class="status-dot online"></span> Systems Online
                </span>
                <a href="{{ route('alerts.index') }}" class="icon-btn">
                    @include('partials.icon', ['name' => 'bell', 'size' => 18])
                    @if ($sidebarAlertCount > 0)<span class="dot-count">{{ $sidebarAlertCount }}</span>@endif
                </a>
            </div>
        </header>

        @if (session('status'))
            <div class="page-body pb-0"><div class="alert alert-success py-2 mb-0">{{ session('status') }}</div></div>
        @endif

        <div class="page-body">
            @yield('content')
        </div>
    </main>
</div>
@stack('scripts')
</body>
</html>
