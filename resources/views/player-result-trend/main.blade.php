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
            'showChartDates' => true,
        ])
    </section>
</main>
