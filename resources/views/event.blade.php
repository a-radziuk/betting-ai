<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Event Odds</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
</head>
<body>
    @include('layouts.partials.betai-header')

    <div class="subbar">
        <div class="container subbar-inner">
            <a class="subbar-back" href="{{ url('/') }}">← Back to events</a>
        </div>
    </div>

    <main class="container">
        <section class="hero">
            <h1>
                {{ $event->homeTeam?->resolvedDisplayName() ?? ('Team #' . $event->home_team_id) }}
                vs
                {{ $event->awayTeam?->resolvedDisplayName() ?? ('Team #' . $event->away_team_id) }}
            </h1>
            <p class="meta">
                Kickoff: {{ $event->start_time?->format('Y-m-d H:i') }} |
                Status: {{ strtoupper($event->status ?? 'unknown') }}
                @if ($event->status === \App\Models\Event::STATUS_FINISHED && filled($event->score))
                    | Final score: {{ $event->score }}
                @endif
            </p>
        </section>

        @include('partials.event-user-bets', [
            'eventBets' => $eventBets ?? collect(),
            'event' => $event,
        ])

        @include('partials.event-analysis', ['eventAnalysis' => $eventAnalysis ?? null])

        @if ($tournament)
            <section class="card event-page-standings" aria-label="League standings">
                <div class="tournament-section-head">
                    <h2 class="tournament-section-title">{{ $tournament->name }}</h2>
                    <a href="{{ route('tournaments.show', $tournament) }}" class="tournament-see-all-link">Full league page</a>
                </div>
                @include('partials.tournament-standings-table', [
                    'tournament' => $tournament,
                    'standingsRows' => $standingsRows,
                    'standingsPromrel' => $standingsPromrel,
                ])
            </section>
        @endif

        @php
            $canPlaceBets = auth()->check()
                && auth()->user()->hasPrivelege(\App\Models\User::PRIVELEGE_PLACE_BETS);
        @endphp
        @if ($canPlaceBets)
            @if ($event->markets->isEmpty())
                <div class="event-empty">No markets available for this event yet.</div>
            @else
                @php
                    $isPending = $event->status === \App\Models\Event::STATUS_SCHEDULED;
                @endphp
                <section class="market-grid">
                    @foreach ($event->markets as $market)
                        <article class="market">
                            <div class="market-head">
                                <span>{{ $market->typeLabel() }}</span>
                                <span class="period">
                                    {{ $market->period }}
                                    @if (! is_null($market->line))
                                        | Line: {{ $market->line }}
                                    @endif
                                </span>
                            </div>
                            <div class="rows">
                                @forelse ($market->selections as $selection)
                                    @php
                                        $odd = $selection->odds->first();
                                        $canBet = $isPending && $odd && $odd->is_active;
                                    @endphp
                                    <div class="row">
                                        @if ($canBet)
                                            <a class="name" href="{{ route('bets.place.show', ['odd' => $odd->id]) }}">
                                                {{ $selection->name }}
                                            </a>
                                            <a class="odds" href="{{ route('bets.place.show', ['odd' => $odd->id]) }}">
                                                {{ number_format((float) $odd->odds, 2) }}
                                            </a>
                                        @else
                                            <span class="name">{{ $selection->name }}</span>
                                            <span class="odds">
                                                {{ number_format(optional($odd)->odds ?? 0, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="row">
                                        <span class="name">No selections</span>
                                        <span class="odds">-</span>
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        @endif
    </main>

    @include('layouts.partials.betai-footer')
</body>
</html>
