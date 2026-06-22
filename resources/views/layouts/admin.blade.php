<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Admin') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<main class="container admin-shell">
    <nav class="admin-sidebar card" aria-label="{{ __('Admin navigation') }}">
        <p class="admin-sidebar-title">{{ __('Admin') }}</p>
        <ul class="admin-sidebar-nav">
            @foreach (array_filter([
                ['label' => __('Users'), 'route' => 'admin.users', 'active' => 'admin.users*'],
                ['label' => __('User Bets'), 'route' => 'admin.user-bets', 'active' => 'admin.user-bets*'],
                ['label' => __('Simple Crypto Payments'), 'route' => 'admin.simple-crypto-payments', 'active' => 'admin.simple-crypto-payments*', 'feature' => 'simple_crypto_payment'],
                ['label' => __('Upload Events'), 'route' => 'admin.upload-events', 'active' => 'admin.upload-events*'],
                ['label' => __('Upload Tournament'), 'route' => 'admin.upload-tournament', 'active' => 'admin.upload-tournament*'],
                ['label' => __('Import standings'), 'route' => 'admin.upload-standings', 'active' => 'admin.upload-standings*'],
                ['label' => __('Upload Analysis'), 'route' => 'admin.upload-analysis', 'active' => 'admin.upload-analysis*'],
                ['label' => __('Upload Predictions'), 'route' => 'admin.upload-predictions', 'active' => 'admin.upload-predictions*'],
                ['label' => __('Resolve Event'), 'route' => 'admin.resolve-event', 'active' => 'admin.resolve-event*'],
                ['label' => __('Site Texts'), 'route' => 'admin.site-texts', 'active' => 'admin.site-texts*'],
                ['label' => __('SEO Pages'), 'route' => 'admin.seo-pages', 'active' => 'admin.seo-pages*'],
                ['label' => __('Legal Pages'), 'route' => 'admin.legal-pages', 'active' => 'admin.legal-pages*'],
                ['label' => __('Blog'), 'route' => 'admin.blogs', 'active' => 'admin.blogs*'],
            ], fn (array $item): bool => ! isset($item['feature']) || feature($item['feature'])) as $item)
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

@include('layouts.partials.betai-footer')
@stack('scripts')
</body>
</html>
