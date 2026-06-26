<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Admin') }}</title>
    @include('layouts.partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<main class="container admin-shell">
    <nav class="admin-sidebar card" aria-label="{{ __('Admin navigation') }}">
        <p class="admin-sidebar-title">{{ __('Admin') }}</p>
        <ul class="admin-sidebar-nav">
            @foreach (\App\Support\AdminNavigation::visibleItems(auth()->user()) as $item)
                <li>
                    <a
                        href="{{ route($item['route']) }}"
                        @class(['admin-sidebar-link', 'admin-sidebar-link--active' => request()->routeIs($item['active'])])
                    >
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
    <div class="admin-main">
        @yield('content')
    </div>
</main>

@include('layouts.partials.betai-footer', ['skipCookieConsent' => true])
@stack('scripts')
</body>
</html>
