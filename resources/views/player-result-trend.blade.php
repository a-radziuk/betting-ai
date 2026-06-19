<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ app_page_title('Result trend') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ route('players.show', ['user' => $player->id]) }}">← {{ __('Back to player') }}</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $player->name }}</h1>
        <p class="meta">{{ __('Full cumulative result trend across all resolved bets.') }}</p>
    </section>

    <section class="card card-pad player-result-trend-card">
        @include('players.partials.wallet-result-chart', [
            'resultChart' => $resultChart,
            'player' => $player,
            'isFullHistory' => true,
            'showFullTrendLink' => false,
        ])
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
