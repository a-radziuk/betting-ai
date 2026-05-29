<main class="container">
    <section class="hero">
        <h1>{{ $tournament->localizedName() }}</h1>
    </section>

    <section class="card tournament-page-upcoming" aria-label="{{ __('Upcoming fixtures') }}">
        @include('partials.upcoming-events-table', ['events' => $upcomingEvents])
    </section>

    <section class="card tournament-page-recent-results" aria-label="{{ __('Latest results') }}">
        <div class="tournament-section-head">
            <h2 class="tournament-section-title">{{ __('Latest results') }}</h2>
            @if ($eventResultsTotal > 0)
                <a href="{{ route('tournaments.results', $tournament) }}" class="tournament-see-all-link">{{ __('See all results') }}</a>
            @endif
        </div>
        @include('partials.event-results-by-date', [
            'eventResults' => $recentEventResults,
            'emptyMessage' => __('No results recorded for this league yet.'),
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
