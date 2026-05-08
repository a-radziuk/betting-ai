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
                {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
                vs
                {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
            </h1>
            <p class="meta">
                Kickoff: {{ $event->start_time?->format('Y-m-d H:i') }} |
                Status: {{ strtoupper($event->status ?? 'unknown') }}
            </p>
        </section>

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
                            <span>{{ $market->type }}</span>
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
    </main>

    @include('layouts.partials.betai-footer')
</body>
</html>
