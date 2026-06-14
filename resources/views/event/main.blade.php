<main class="container">
    <section class="hero">
        <h1>
            {{ $event->homeTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event->home_team_id]) }}
            {{ __('vs') }}
            {{ $event->awayTeam?->resolvedDisplayName() ?? __('Team #:id', ['id' => $event->away_team_id]) }}
        </h1>
        <p class="meta">
            {{ __('Kickoff') }}: {{ $event->start_time?->format('Y-m-d H:i') }} |
            {{ __('Status') }}: {{ $event->statusLabel() }}
            @if ($event->status === \App\Models\Event::STATUS_FINISHED && filled($event->score))
                | {{ __('Final score') }}: {{ $event->score }}
            @endif
        </p>
    </section>

    @include('partials.event-user-bets', [
        'eventBets' => $eventBets ?? collect(),
        'event' => $event,
    ])

    @include('partials.event-analysis', ['eventAnalysis' => $eventAnalysis ?? null])

    @if ($tournament)
        <section class="card event-page-standings" aria-label="{{ __('League standings') }}">
            <div class="tournament-section-head">
                <h2 class="tournament-section-title">{{ $tournament->localizedName() }}</h2>
                <a href="{{ route('tournaments.show', $tournament) }}" class="tournament-see-all-link">{{ __('Full league page') }}</a>
            </div>
            @include('partials.tournament-standings-table', [
                'tournament' => $tournament,
                'standingsRows' => $standingsRows,
                'standingsGroups' => $standingsGroups ?? [],
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
            <div class="event-empty">{{ __('No markets available for this event yet.') }}</div>
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
                                    | {{ __('Line') }}: {{ $market->line }}
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
                                            {{ $selection->displayName($event) }}
                                        </a>
                                        <a class="odds" href="{{ route('bets.place.show', ['odd' => $odd->id]) }}">
                                            {{ number_format((float) $odd->odds, 2) }}
                                        </a>
                                    @else
                                        <span class="name">{{ $selection->displayName($event) }}</span>
                                        <span class="odds">
                                            {{ number_format(optional($odd)->odds ?? 0, 2) }}
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div class="row">
                                    <span class="name">{{ __('No selections') }}</span>
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
