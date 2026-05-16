<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | {{ $tournament->name }} — Standings</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
@include('layouts.partials.betai-header')

<div class="subbar">
    <div class="container subbar-inner">
        <a class="subbar-back" href="{{ url('/') }}">← Back to home</a>
    </div>
</div>

<main class="container">
    <section class="hero">
        <h1>{{ $tournament->name }}</h1>
    </section>

    <section class="card tournament-page-upcoming" aria-label="Upcoming fixtures">
        @include('partials.upcoming-events-table', ['events' => $upcomingEvents])
    </section>

    <section class="card tournament-page-recent-results" aria-label="Latest results">
        <div class="tournament-section-head">
            <h2 class="tournament-section-title">Latest results</h2>
            @if ($eventResultsTotal > 0)
                <a href="{{ route('tournaments.results', $tournament) }}" class="tournament-see-all-link">See all results</a>
            @endif
        </div>
        @include('partials.event-results-by-date', [
            'eventResults' => $recentEventResults,
            'emptyMessage' => 'No results recorded for this league yet.',
        ])
    </section>

    <section class="card">
        @include('partials.tournament-standings-table', [
            'tournament' => $tournament,
            'standingsRows' => $standingsRows,
            'standingsPromrel' => $standingsPromrel,
        ])
    </section>
</main>

@include('layouts.partials.betai-footer')
</body>
</html>
