<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name', 'Loom') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('styles')
</head>
<body>
    <div class="admin-sidebar-overlay" id="sidebar-overlay"></div>

    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar-brand d-flex align-items-center justify-content-between">
            <div>
                <h1>{{ config('app.name', 'Loom') }}</h1>
                <span>Admin Panel</span>
            </div>
            <button class="admin-sidebar-close d-lg-none" id="sidebar-close" aria-label="Close sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="admin-nav">
            <a href="{{ route('admin.index') }}" class="admin-nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                Dashboard
            </a>

            @include('admin.partials.navigation')
        </nav>

        <div class="admin-sidebar-footer">
            <a href="{{ route('admin.settings') }}" class="admin-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i>
                Settings
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-topbar-btn d-lg-none" id="sidebar-toggle" aria-label="Open sidebar">
                <i class="bi bi-list"></i>
            </button>

            <h1 class="admin-topbar-title">@yield('page-title', 'Dashboard')</h1>

            <div class="admin-topbar-actions">
                <button class="admin-topbar-btn" aria-label="Notifications" data-bs-toggle="tooltip" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="badge-dot"></span>
                </button>

                <div class="dropdown">
                    <div class="admin-avatar dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                        AD
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Admin User</h6></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="admin-content">
            @yield('content')
        </main>
    </div>

    @include('admin.partials.confirm-modal')
    @include('admin.partials.media-parameter-modal')
    @include('admin.partials.url-parameter-modal')
    @include('admin.partials.media-finder-modal')

    <div class="toast-container position-fixed top-0 end-0 p-3 admin-flash-toasts"
         id="admin-toast-container"
         aria-live="polite"
         aria-atomic="true">
        @include('admin.partials.flash-toasts')
    </div>

    @stack('scripts')
</body>
</html>
