<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.seo-meta', ['seo' => $seo ?? []])
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
    </head>
<body>
    @include('layouts.partials.betai-header')

    <main class="container">
        @include('partials.home-hero-banner')

        @if ($showHomePromocode)
            @include('partials.home-promocode-line')
        @endif

        {!! $mainHtml !!}
    </main>

    @include('layouts.partials.betai-footer')
    </body>
</html>
