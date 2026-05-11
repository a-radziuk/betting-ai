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

        <section class="card">
            @if ($events->isEmpty())
                <div class="empty">No upcoming events found. Seed more data and refresh.</div>
                    @else
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Match</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr data-clickable onclick="window.location='{{ route('events.show', $event) }}'">
                                <td>{{ $event->start_time->format('Y-m-d H:i') }}</td>
                                <td>
                                    {{ $event->homeTeam?->name ?? ('Team #' . $event->home_team_id) }}
                                    vs
                                    {{ $event->awayTeam?->name ?? ('Team #' . $event->away_team_id) }}
                                </td>
                                <td>
                                    <span class="status">{{ strtoupper($event->status ?? 'unknown') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
            </main>

    @include('layouts.partials.betai-footer')
    </body>
</html>
