<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('BetAI | :tournament — Results', ['tournament' => $tournament->localizedName()]) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('tournaments.show', $tournament) }}">← {{ __('Back to :tournament', ['tournament' => $tournament->localizedName()]) }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $tournament->localizedName() }}</h1>
        <p class="meta">{{ __('All results') }}</p>
    </section>

    <section class="card tournament-page-all-results" aria-label="{{ __('All results') }}">
        @include('partials.event-results-by-date', [
            'eventResults' => $allEventResults,
            'emptyMessage' => __('No results recorded for this league yet.'),
        ])
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
