<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | :title', ['title' => $page->title]) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ url('/') }}">← {{ __('Back to home') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $page->title }}</h1>
        @if ($page->updated_at)
            <p class="meta">{{ __('Last updated') }}: {{ $page->updated_at->format('Y-m-d') }}</p>
        @endif
    </section>

    <section class="card legal-page-card">
        <div class="legal-page-body">
            {!! $renderedContent !!}
        </div>
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
