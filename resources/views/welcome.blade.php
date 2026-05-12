<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BetAI | Upcoming Football Events</title>
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.betai-styles')
    </head>
<body>
    @include('layouts.partials.betai-header')

    <main class="container">
        <section class="hero">
            <h1>Nearest 20 Upcoming Events</h1>
            <p>Real-time lineup of the next football fixtures sorted by kickoff time.</p>
            <p class="meta" style="margin-top: 0.9rem;">
                <a class="header-link" href="{{ route('players.index') }}">Players</a>
            </p>
        </section>

        @if (isset($topTournaments) && $topTournaments->isNotEmpty())
            <section class="card tournament-leagues-line" aria-label="Featured tournaments">
                <div class="tournament-leagues-line-inner">
                    @foreach ($topTournaments as $t)
                        <a href="{{ route('tournaments.show', $t) }}" class="tournament-league-link">{{ $t->name }}</a>
                        @if (! $loop->last)
                            <span class="tournament-league-sep" aria-hidden="true">·</span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif

        @if (isset($topBettors) && $topBettors->isNotEmpty())
            <section class="welcome-top-bettors" aria-labelledby="welcome-top-bettors-title">
                <h2 id="welcome-top-bettors-title" class="welcome-top-bettors-title">Top bettors</h2>
                <p class="welcome-top-bettors-lead">Players with at least one bet, ranked by lifetime wallet result.</p>
                <div class="welcome-top-bettors-grid">
                    @foreach ($topBettors as $index => $user)
                        @php
                            $wallet = $user->wallet;
                            $currency = $wallet?->currency ?? 'EUR';
                            $betCount = (int) ($user->bets_count ?? 0);
                            $stakeSum = (float) ($user->bets_sum_stake ?? 0);
                            $total = $wallet ? (float) $wallet->total_result : 0.0;
                        @endphp
                        <article class="welcome-bettor-card">
                            <div class="welcome-bettor-card-rank" aria-hidden="true">{{ $index + 1 }}</div>
                            <div class="welcome-bettor-card-body">
                                <h3 class="welcome-bettor-card-name">{{ $user->name }}</h3>
                                <p class="welcome-bettor-card-bets-meta">
                                    <span class="welcome-bettor-card-bets-count">{{ $betCount }} {{ $betCount === 1 ? 'bet' : 'bets' }}</span>
                                    <span class="welcome-bettor-card-bets-sep" aria-hidden="true">·</span>
                                    <span class="welcome-bettor-card-bets-stake">{{ number_format($stakeSum, 2) }} {{ $currency }}</span>
                                </p>
                                <p @class([
                                    'welcome-bettor-card-result',
                                    'welcome-bettor-card-result--pos' => $total >= 0,
                                    'welcome-bettor-card-result--neg' => $total < 0,
                                ])>
                                    {{ $total >= 0 ? '+' : '' }}{{ number_format($total, 2) }} {{ $currency }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="card">
            @if ($events->isEmpty())
                <div class="empty">No upcoming events found. Seed more data and refresh.</div>
            @else
                @php
                    $tz = config('app.timezone');
                    $todayKey = now()->timezone($tz)->format('Y-m-d');
                    $eventsByDay = $events->groupBy(fn ($e) => $e->start_time->timezone($tz)->format('Y-m-d'));
                @endphp
                @foreach ($eventsByDay as $dayKey => $dayEvents)
                    <div class="welcome-events-section">
                        <h2 class="welcome-events-section-title">
                            @if ($dayKey === $todayKey)
                                Today
                            @else
                                {{ \Carbon\Carbon::createFromFormat('Y-m-d', $dayKey, $tz)->locale(app()->getLocale())->translatedFormat('l, j F Y') }}
                            @endif
                        </h2>
                        <table class="welcome-events-table">
                            <thead>
                                <tr>
                                    <th class="welcome-time-col">Time</th>
                                    <th class="welcome-tournament-col">Tournament</th>
                                    <th class="welcome-match-col">Match</th>
                                    <th class="welcome-1x2-col">Home</th>
                                    <th class="welcome-1x2-col">Draw</th>
                                    <th class="welcome-1x2-col">Away</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dayEvents as $event)
                                    @php
                                        $matchResult = $event->markets->firstWhere('period', \App\Models\Market::PERIOD_FULL_TIME)
                                            ?? $event->markets->first();
                                        $bySelection = $matchResult ? $matchResult->selections->keyBy('name') : collect();
                                        $oddStr = function (?string $name) use ($bySelection) {
                                            $odd = $bySelection->get($name)?->odds->first();

                                            return $odd !== null ? number_format((float) $odd->odds, 2) : '—';
                                        };
                                        $kickoffTime = $event->start_time->timezone($tz)->translatedFormat('H:i');
                                        $tournamentName = $event->tournament?->name ?? '—';
                                    @endphp
                                    <tr data-clickable onclick="window.location='{{ route('events.show', $event) }}'">
                                        <td class="welcome-time-col">
                                            <time datetime="{{ $event->start_time->toIso8601String() }}">
                                                {{ $kickoffTime }}
                                            </time>
                                        </td>
                                        <td class="welcome-tournament-col">{{ $tournamentName }}</td>
                                        <td class="welcome-match-col">
                                            <div class="welcome-match-teams">
                                                {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
                                                vs
                                                {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
                                            </div>
                                            <div class="welcome-match-meta">
                                                <time datetime="{{ $event->start_time->toIso8601String() }}" class="welcome-match-meta-time">{{ $kickoffTime }}</time>
                                                <span class="welcome-match-meta-sep" aria-hidden="true">·</span>
                                                <span class="welcome-match-meta-tournament">{{ $tournamentName }}</span>
                                            </div>
                                        </td>
                                        <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_HOME) }}</td>
                                        <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_DRAW) }}</td>
                                        <td class="welcome-odds welcome-1x2-col">{{ $oddStr(\App\Models\Selection::NAME_AWAY) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif
        </section>
            </main>

    @include('layouts.partials.betai-footer')
    </body>
</html>
